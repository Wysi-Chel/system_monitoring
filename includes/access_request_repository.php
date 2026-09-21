<?php
const ACCESS_REQUEST_CSRF_SESSION_KEY = "system_monitoring_access_request_csrf";
const ACCESS_REQUEST_SUBMITTED_AT_SESSION_KEY = "system_monitoring_access_request_submitted_at";
const ACCESS_REQUEST_SUBMIT_COOLDOWN_SECONDS = 45;
const ACCESS_REQUEST_NAME_MAX_LENGTH = 150;
const ACCESS_REQUEST_USERNAME_MAX_LENGTH = 100;
const ACCESS_REQUEST_DESCRIPTION_MAX_LENGTH = 2000;
const ACCESS_REQUEST_ACCESS_DETAILS_MAX_LENGTH = 255;
const ACCESS_REQUEST_REVIEW_NOTES_MAX_LENGTH = 2000;

function getAccessRequestCsrfToken(): string
{
    startMonitoringSession();

    $token = $_SESSION[ACCESS_REQUEST_CSRF_SESSION_KEY] ?? "";
    if (!is_string($token) || $token === "") {
        $token = bin2hex(random_bytes(32));
        $_SESSION[ACCESS_REQUEST_CSRF_SESSION_KEY] = $token;
    }

    return $token;
}

function isValidAccessRequestCsrfToken($token): bool
{
    return is_string($token) && $token !== "" && hash_equals(getAccessRequestCsrfToken(), $token);
}

function isAccessRequestSubmissionAllowed(): bool
{
    startMonitoringSession();
    $lastSubmittedAt = (int) ($_SESSION[ACCESS_REQUEST_SUBMITTED_AT_SESSION_KEY] ?? 0);

    return $lastSubmittedAt === 0 || (time() - $lastSubmittedAt) >= ACCESS_REQUEST_SUBMIT_COOLDOWN_SECONDS;
}

function markAccessRequestSubmission(): void
{
    startMonitoringSession();
    $_SESSION[ACCESS_REQUEST_SUBMITTED_AT_SESSION_KEY] = time();
}

function getAccessRequestClientIp(): string
{
    return substr(trim((string) ($_SERVER["REMOTE_ADDR"] ?? "unknown")), 0, 45);
}

function getAccessRequestTextLength(string $value): int
{
    return function_exists("mb_strlen") ? mb_strlen($value, "UTF-8") : strlen($value);
}

function getAccessRequestPortalUserName(): string
{
    $portalUser = getMonitoringPortalUser();
    $userName = trim((string) ($portalUser["full_name"] ?? $portalUser["name"] ?? $portalUser["username"] ?? ""));
    return $userName !== "" ? $userName : "Portal user";
}

function getAccessRequestDealerOptions(array $companyConfigs): array
{
    $dealerOptions = [];

    foreach ($companyConfigs as $company) {
        foreach ($company["access_request_dealers"] ?? [] as $dealer) {
            if (is_string($dealer) && !in_array($dealer, $dealerOptions, true)) {
                $dealerOptions[] = $dealer;
            }
        }
    }

    // "All Dealers" is routed through one company's list but belongs after the specific dealers.
    if (in_array("All Dealers", $dealerOptions, true)) {
        $dealerOptions = [...array_diff($dealerOptions, ["All Dealers"]), "All Dealers"];
    }

    return $dealerOptions;
}

function resolveAccessRequestCompanyForDealer(string $dealer, array $companyConfigs): ?array
{
    foreach ($companyConfigs as $company) {
        if (in_array($dealer, $company["access_request_dealers"] ?? [], true)) {
            return $company;
        }
    }

    return null;
}

function getAccessRequestStatusClass(string $status): string
{
    $statusClass = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($status))), "-");
    return $statusClass !== "" ? $statusClass : "unknown";
}

function getAccessRequestManilaTimestamp(): string
{
    return (new DateTimeImmutable("now", new DateTimeZone("Asia/Manila")))->format("Y-m-d H:i:s");
}

function getAccessRequestManilaToday(): string
{
    return (new DateTimeImmutable("now", new DateTimeZone("Asia/Manila")))->format("Y-m-d");
}

// A request keyed in from inside the system is credited to the side that encoded it, so a
// walk-in or phoned-in request still says where it came from. The public form keeps naming a manager.
function getAccessRequestEncoderSideOptions(): array
{
    return ["IT", "SA"];
}

function getAccessRequestEncoderSide(): string
{
    return isMonitoringSuperAdmin() ? "SA" : "IT";
}

// The public form and the internal encoding form capture the same request, so they share these rules.
function validateAccessRequestValues(array $values): array
{
    $errors = [];

    if (
        ($values["requester_name"] ?? "") === ""
        || ($values["dealer"] ?? "") === ""
        || ($values["department"] ?? "") === ""
        || ($values["dmis_username"] ?? "") === ""
        || ($values["position"] ?? "") === ""
        || ($values["description"] ?? "") === ""
        || ($values["requested_by"] ?? "") === ""
    ) {
        $errors[] = "Complete all required fields.";
    }

    if (($values["modules"] ?? []) === []) {
        $errors[] = "Select at least one module.";
    }

    $dateOfRequest = (string) ($values["date_of_request"] ?? "");
    if ($dateOfRequest === "") {
        $errors[] = "Enter the date of request as a valid date.";
    } elseif ($dateOfRequest > getAccessRequestManilaToday()) {
        $errors[] = "The date of request cannot be in the future.";
    }

    if (
        getAccessRequestTextLength((string) ($values["requester_name"] ?? "")) > ACCESS_REQUEST_NAME_MAX_LENGTH
        || getAccessRequestTextLength((string) ($values["requested_by"] ?? "")) > ACCESS_REQUEST_NAME_MAX_LENGTH
        || getAccessRequestTextLength((string) ($values["dmis_username"] ?? "")) > ACCESS_REQUEST_USERNAME_MAX_LENGTH
        || getAccessRequestTextLength((string) ($values["position"] ?? "")) > ACCESS_REQUEST_USERNAME_MAX_LENGTH
    ) {
        $errors[] = "A name or the DMIS username is too long.";
    }

    if (getAccessRequestTextLength((string) ($values["description"] ?? "")) > ACCESS_REQUEST_DESCRIPTION_MAX_LENGTH) {
        $errors[] = "Keep the description under " . ACCESS_REQUEST_DESCRIPTION_MAX_LENGTH . " characters.";
    }

    return $errors;
}

function normalizeAccessRequestModules($selectedModules, array $moduleOptions): array
{
    if (!is_array($selectedModules)) {
        return [];
    }

    // Follow the option order so the stored list reads the same regardless of tick order.
    return array_values(array_filter(
        $moduleOptions,
        static fn(string $option): bool => in_array($option, $selectedModules, true)
    ));
}

function decodeAccessRequestGrantAccess(?string $value): array
{
    $decoded = json_decode((string) $value, true);
    if (!is_array($decoded)) {
        return [];
    }

    $grantAccess = [];
    foreach ($decoded as $module => $details) {
        $module = trim((string) $module);
        if ($module !== "" && !is_array($details)) {
            $grantAccess[$module] = mb_strtoupper(trim((string) $details), 'UTF-8');
        }
    }

    return $grantAccess;
}

function buildAccessRequestGrantAccess(array $input, array $moduleOptions): array
{
    $accessDetails = is_array($input["grant_access"] ?? null) ? $input["grant_access"] : [];
    $grantAccess = [];

    foreach (normalizeAccessRequestModules($input["grant_modules"] ?? [], $moduleOptions) as $module) {
        $details = $accessDetails[$module] ?? "";
        $grantAccess[$module] = is_string($details) ? mb_strtoupper(trim($details), 'UTF-8') : "";
    }

    return $grantAccess;
}

function buildAccessRequestComparisonRows(array $requestedModules, array $grantAccess): array
{
    $rows = [];

    foreach ($grantAccess as $module => $details) {
        $rows[] = [
            "module" => $module,
            "details" => $details,
            "change" => in_array($module, $requestedModules, true) ? "" : "added",
        ];
    }

    foreach ($requestedModules as $module) {
        if (!array_key_exists($module, $grantAccess)) {
            $rows[] = [
                "module" => $module,
                "details" => "",
                "change" => "not_included",
            ];
        }
    }

    return $rows;
}

function buildAccessRequestApprovedAccess(array $input, array $grantAccess): array
{
    $approvedModules = is_array($input["approve_modules"] ?? null) ? $input["approve_modules"] : [];

    // Only modules IT sent for final review can be approved.
    return array_filter(
        $grantAccess,
        static fn($module): bool => in_array((string) $module, $approvedModules, true),
        ARRAY_FILTER_USE_KEY
    );
}

function buildAccessRequestFinalReviewRows(array $grantAccess, array $approvedAccess): array
{
    $rows = [];

    foreach ($grantAccess as $module => $details) {
        $rows[] = [
            "module" => $module,
            "details" => $details,
            "change" => array_key_exists($module, $approvedAccess) ? "approved" : "not_approved",
        ];
    }

    return $rows;
}

// IT accounts send the access to grant and implement it; the super admin gives the final review.
function isAccessRequestItReviewer(): bool
{
    return !isMonitoringSuperAdmin();
}

function isAccessRequestFinalReviewer(): bool
{
    return isMonitoringSuperAdmin();
}

function getAccessRequestRecordStatus(array $record): string
{
    return trim((string) ($record["status"] ?? ""));
}

function canSubmitAccessRequestItReview(array $record): bool
{
    // IT can revise the access to grant until the final review approves it.
    return in_array(getAccessRequestRecordStatus($record), ["Pending", "For Approval", "Declined"], true);
}

function canSaveAccessRequestFinalReview(array $record): bool
{
    return getAccessRequestRecordStatus($record) === "For Approval";
}

function canMarkAccessRequestImplemented(array $record): bool
{
    return getAccessRequestRecordStatus($record) === "Approved";
}

function buildNextAccessRequestReference(PDO $pdo, array $company): string
{
    $tableNameSql = quoteMysqlIdentifier($company["access_request_table_name"]);
    $year = (new DateTimeImmutable("now", new DateTimeZone("Asia/Manila")))->format("Y");
    $prefix = "DAR-" . uppercaseText((string) ($company["export_slug"] ?? $company["key"])) . "-" . $year . "-";

    $stmt = $pdo->prepare(
        "SELECT reference_no
         FROM {$tableNameSql}
         WHERE reference_no LIKE :prefix ESCAPE '\\\\'
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([":prefix" => escapeLikeTerm($prefix) . "%"]);
    $lastReference = (string) ($stmt->fetchColumn() ?: "");
    $nextSequence = $lastReference !== "" ? ((int) substr($lastReference, -4)) + 1 : 1;

    return $prefix . str_pad((string) $nextSequence, 4, "0", STR_PAD_LEFT);
}

function insertAccessRequest(PDO $pdo, array $company, array $values): string
{
    $tableNameSql = quoteMysqlIdentifier($company["access_request_table_name"]);
    $stmt = $pdo->prepare(
        "INSERT INTO {$tableNameSql} (
            reference_no,
            requester_name,
            dealer,
            department,
            dmis_username,
            position,
            date_of_request,
            module,
            description,
            requested_by,
            encoded_by,
            status,
            submitted_ip
        ) VALUES (
            :reference_no,
            :requester_name,
            :dealer,
            :department,
            :dmis_username,
            :position,
            :date_of_request,
            :module,
            :description,
            :requested_by,
            :encoded_by,
            :status,
            :submitted_ip
        )"
    );

    // Two submissions in the same instant can compute the same next reference. The unique
    // key rejects the second one, which then retries and takes the following number.
    for ($attempt = 1; $attempt <= 3; $attempt += 1) {
        $referenceNo = buildNextAccessRequestReference($pdo, $company);

        try {
            $stmt->execute([
                ":reference_no" => $referenceNo,
                ":requester_name" => $values["requester_name"],
                ":dealer" => $values["dealer"],
                ":department" => $values["department"],
                ":dmis_username" => $values["dmis_username"],
                ":position" => $values["position"],
                ":date_of_request" => $values["date_of_request"] ?? null,
                ":module" => $values["module"],
                ":description" => $values["description"],
                ":requested_by" => $values["requested_by"],
                ":encoded_by" => ($values["encoded_by"] ?? "") !== "" ? $values["encoded_by"] : null,
                ":status" => $values["status"],
                ":submitted_ip" => $values["submitted_ip"],
            ]);

            return $referenceNo;
        } catch (PDOException $exception) {
            $isDuplicateReference = (int) ($exception->errorInfo[1] ?? 0) === 1062;
            if (!$isDuplicateReference || $attempt === 3) {
                throw $exception;
            }
        }
    }

    throw new RuntimeException("access_request_reference_unavailable");
}

function buildAccessRequestFilters(array $input, array $filterOptions): array
{
    $filters = [
        "search" => normalizeSearchFilter($input["q"] ?? ""),
        "dealer" => normalizeAllowedFilter($input["dealer"] ?? "", $filterOptions["dealer"] ?? []),
        "status" => normalizeAllowedFilter($input["status"] ?? "", $filterOptions["status"] ?? []),
        "page" => normalizePositiveInt($input["page"] ?? 1, 1),
        "per_page" => normalizePositiveInt($input["per_page"] ?? 25, 25),
    ];

    if (!in_array($filters["per_page"], $filterOptions["per_page"] ?? [25], true)) {
        $filters["per_page"] = 25;
    }

    return $filters;
}

function buildAccessRequestWhereClause(array $filters, array &$bindings): string
{
    $conditions = [];
    $bindings = [];

    if (($filters["search"] ?? "") !== "") {
        $searchValue = "%" . escapeLikeTerm($filters["search"]) . "%";
        $searchColumns = [
            "reference_no",
            "requester_name",
            "dmis_username",
            "position",
            "module",
            "department",
            "description",
            "requested_by",
        ];

        $searchParts = [];
        foreach ($searchColumns as $index => $columnName) {
            $paramKey = "access_search_" . $index;
            $searchParts[] = $columnName . " LIKE :" . $paramKey . " ESCAPE '\\\\'";
            $bindings[$paramKey] = $searchValue;
        }

        $conditions[] = "(" . implode(" OR ", $searchParts) . ")";
    }

    if (($filters["dealer"] ?? "") !== "") {
        $conditions[] = "dealer = :dealer";
        $bindings["dealer"] = $filters["dealer"];
    }

    if (($filters["status"] ?? "") !== "") {
        $conditions[] = "status = :status";
        $bindings["status"] = $filters["status"];
    }

    return $conditions === [] ? "" : " WHERE " . implode(" AND ", $conditions);
}

function countAccessRequests(PDO $pdo, string $tableNameSql, array $filters): int
{
    $bindings = [];
    $whereClause = buildAccessRequestWhereClause($filters, $bindings);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tableNameSql}{$whereClause}");

    foreach ($bindings as $key => $value) {
        $stmt->bindValue(":" . $key, $value, PDO::PARAM_STR);
    }

    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function fetchAccessRequests(
    PDO $pdo,
    string $tableNameSql,
    array $filters,
    array $statusOrder,
    ?int $limit = null,
    int $offset = 0
): array {
    $bindings = [];
    $whereClause = buildAccessRequestWhereClause($filters, $bindings);
    $statusOrderSql = $statusOrder !== []
        ? "FIELD(status, " . implode(", ", array_map([$pdo, "quote"], $statusOrder)) . "), "
        : "";

    $sql = "SELECT * FROM {$tableNameSql}{$whereClause} ORDER BY {$statusOrderSql}created_at DESC, id DESC";
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $pdo->prepare($sql);

    foreach ($bindings as $key => $value) {
        $stmt->bindValue(":" . $key, $value, PDO::PARAM_STR);
    }

    if ($limit !== null) {
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", max(0, $offset), PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countAccessRequestsByStatus(PDO $pdo, string $tableNameSql, array $statusOptions): array
{
    $counts = array_fill_keys($statusOptions, 0);
    $rows = $pdo->query("SELECT status, COUNT(*) AS total FROM {$tableNameSql} GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $status = (string) ($row["status"] ?? "");
        if (array_key_exists($status, $counts)) {
            $counts[$status] = (int) $row["total"];
        }
    }

    return $counts;
}

function fetchAccessRequestNotifications(PDO $pdo, string $tableNameSql, array $statuses, int $limit = 5): array
{
    $notifications = [];

    foreach ($statuses as $status) {
        $filters = ["search" => "", "dealer" => "", "status" => $status];
        $count = countAccessRequests($pdo, $tableNameSql, $filters);
        $notifications[$status] = [
            "count" => $count,
            "records" => $count > 0 ? fetchAccessRequests($pdo, $tableNameSql, $filters, [], $limit) : [],
        ];
    }

    return $notifications;
}

function fetchAccessRequestById(PDO $pdo, string $tableNameSql, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM {$tableNameSql} WHERE id = :id LIMIT 1");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record === false ? null : $record;
}

function submitAccessRequestItReview(
    PDO $pdo,
    string $tableNameSql,
    int $id,
    array $grantAccess,
    ?string $itNotes,
    string $reviewedBy
): void {
    // Submitting, including a resubmission after a decline, asks the final review for a new decision.
    $stmt = $pdo->prepare(
        "UPDATE {$tableNameSql}
         SET grant_access = :grant_access,
             it_notes = :it_notes,
             it_reviewed_by = :it_reviewed_by,
             it_reviewed_at = :it_reviewed_at,
             final_decision = NULL,
             approved_access = NULL,
             status = 'For Approval'
         WHERE id = :id
           AND status IN ('Pending', 'For Approval', 'Declined')"
    );
    $stmt->bindValue(":grant_access", json_encode($grantAccess, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
    $stmt->bindValue(":it_notes", $itNotes, $itNotes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":it_reviewed_by", $reviewedBy, PDO::PARAM_STR);
    $stmt->bindValue(":it_reviewed_at", getAccessRequestManilaTimestamp(), PDO::PARAM_STR);
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
}

function saveAccessRequestFinalReview(
    PDO $pdo,
    string $tableNameSql,
    int $id,
    array $approvedAccess,
    ?string $finalNotes,
    string $reviewedBy,
    string $reviewedItSubmissionAt
): bool {
    // Approving at least one module sends the request to IT for implementation; approving none declines it.
    // The decision only applies to the IT submission the reviewer saw; a resubmission changes it_reviewed_at.
    $decision = $approvedAccess !== [] ? "Approved" : "Declined";
    $approvedAccessJson = $approvedAccess !== [] ? json_encode($approvedAccess, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $pdo->prepare(
        "UPDATE {$tableNameSql}
         SET final_decision = :final_decision,
             approved_access = :approved_access,
             final_notes = :final_notes,
             final_reviewed_by = :final_reviewed_by,
             final_reviewed_at = :final_reviewed_at,
             status = :status
         WHERE id = :id
           AND status = 'For Approval'
           AND it_reviewed_at = :it_reviewed_at"
    );
    $stmt->bindValue(":final_decision", $decision, PDO::PARAM_STR);
    $stmt->bindValue(":approved_access", $approvedAccessJson, $approvedAccessJson === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":final_notes", $finalNotes, $finalNotes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":final_reviewed_by", $reviewedBy, PDO::PARAM_STR);
    $stmt->bindValue(":final_reviewed_at", getAccessRequestManilaTimestamp(), PDO::PARAM_STR);
    $stmt->bindValue(":status", $decision, PDO::PARAM_STR);
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":it_reviewed_at", $reviewedItSubmissionAt, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->rowCount() > 0;
}

function markAccessRequestImplemented(
    PDO $pdo,
    array $company,
    int $id,
    ?string $implementationNotes,
    string $implementedBy
): bool {
    $requestTableNameSql = quoteMysqlIdentifier($company["access_request_table_name"]);
    $userAccessTableNameSql = quoteMysqlIdentifier($company["user_access_table_name"]);
    $implementedAt = getAccessRequestManilaTimestamp();

    $pdo->beginTransaction();

    try {
        // Lock the request so its approved access is recorded exactly once.
        $recordStmt = $pdo->prepare("SELECT * FROM {$requestTableNameSql} WHERE id = :id LIMIT 1 FOR UPDATE");
        $recordStmt->bindValue(":id", $id, PDO::PARAM_INT);
        $recordStmt->execute();
        $record = $recordStmt->fetch(PDO::FETCH_ASSOC);

        if ($record === false || !canMarkAccessRequestImplemented($record)) {
            $pdo->rollBack();
            return false;
        }

        $updateStmt = $pdo->prepare(
            "UPDATE {$requestTableNameSql}
             SET it_status = 'Implemented',
                 implementation_notes = :implementation_notes,
                 implemented_by = :implemented_by,
                 implemented_at = :implemented_at,
                 status = 'Implemented'
             WHERE id = :id"
        );
        $updateStmt->bindValue(":implementation_notes", $implementationNotes, $implementationNotes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $updateStmt->bindValue(":implemented_by", $implementedBy, PDO::PARAM_STR);
        $updateStmt->bindValue(":implemented_at", $implementedAt, PDO::PARAM_STR);
        $updateStmt->bindValue(":id", $id, PDO::PARAM_INT);
        $updateStmt->execute();

        $accessStmt = $pdo->prepare(
            "INSERT INTO {$userAccessTableNameSql} (
                dmis_username,
                position,
                requester_name,
                dealer,
                department,
                module,
                access_details,
                access_request_id,
                reference_no,
                implemented_by,
                approved_by,
                granted_at
            ) VALUES (
                :dmis_username,
                :position,
                :requester_name,
                :dealer,
                :department,
                :module,
                :access_details,
                :access_request_id,
                :reference_no,
                :implemented_by,
                :approved_by,
                :granted_at
            )
            ON DUPLICATE KEY UPDATE
                requester_name = VALUES(requester_name),
                dealer = VALUES(dealer),
                department = VALUES(department),
                access_details = VALUES(access_details),
                access_request_id = VALUES(access_request_id),
                reference_no = VALUES(reference_no),
                implemented_by = VALUES(implemented_by),
                approved_by = VALUES(approved_by),
                granted_at = VALUES(granted_at)"
        );

        foreach (decodeAccessRequestGrantAccess($record["approved_access"] ?? null) as $module => $details) {
            $accessStmt->execute([
                ":dmis_username" => $record["dmis_username"],
                ":position" => $record["position"],
                ":requester_name" => $record["requester_name"],
                ":dealer" => $record["dealer"],
                ":department" => $record["department"],
                ":module" => $module,
                ":access_details" => $details !== "" ? $details : null,
                ":access_request_id" => (int) $record["id"],
                ":reference_no" => $record["reference_no"],
                ":implemented_by" => $implementedBy,
                ":approved_by" => $record["final_reviewed_by"],
                ":granted_at" => $implementedAt,
            ]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

function buildAccessRequestFilterBadges(array $filters): array
{
    $badges = [];

    if (($filters["search"] ?? "") !== "") {
        $badges[] = 'Search: "' . $filters["search"] . '"';
    }

    if (($filters["dealer"] ?? "") !== "") {
        $badges[] = "Dealers: " . $filters["dealer"];
    }

    if (($filters["status"] ?? "") !== "") {
        $badges[] = "Status: " . $filters["status"];
    }

    return $badges;
}

// The request date is read back from the originating request so accesses recorded earlier show it too.
function fetchUserAccessesByUsername(
    PDO $pdo,
    string $tableNameSql,
    string $dmisUsername,
    string $accessRequestTableNameSql
): array {
    $stmt = $pdo->prepare(
        "SELECT ua.*, ar.created_at AS request_created_at
         FROM {$tableNameSql} ua
         LEFT JOIN {$accessRequestTableNameSql} ar ON ar.id = ua.access_request_id
         WHERE ua.dmis_username = :dmis_username
         ORDER BY ua.module ASC"
    );
    $stmt->execute([":dmis_username" => $dmisUsername]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildUserAccessWhereClause(array $filters, array &$bindings): string
{
    $conditions = [];
    $bindings = [];

    if (($filters["search"] ?? "") !== "") {
        $searchValue = "%" . escapeLikeTerm($filters["search"]) . "%";
        $searchColumns = [
            "dmis_username",
            "position",
            "requester_name",
            "department",
            "module",
            "access_details",
            "reference_no",
        ];

        $searchParts = [];
        foreach ($searchColumns as $index => $columnName) {
            $paramKey = "user_access_search_" . $index;
            $searchParts[] = $columnName . " LIKE :" . $paramKey . " ESCAPE '\\\\'";
            $bindings[$paramKey] = $searchValue;
        }

        $conditions[] = "(" . implode(" OR ", $searchParts) . ")";
    }

    if (($filters["dealer"] ?? "") !== "") {
        $conditions[] = "dealer = :dealer";
        $bindings["dealer"] = $filters["dealer"];
    }

    return $conditions === [] ? "" : " WHERE " . implode(" AND ", $conditions);
}

function countUserAccessUsers(PDO $pdo, string $tableNameSql, array $filters): int
{
    $bindings = [];
    $whereClause = buildUserAccessWhereClause($filters, $bindings);
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT dmis_username) FROM {$tableNameSql}{$whereClause}");

    foreach ($bindings as $key => $value) {
        $stmt->bindValue(":" . $key, $value, PDO::PARAM_STR);
    }

    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function fetchUserAccessGroups(
    PDO $pdo,
    string $tableNameSql,
    array $filters,
    int $limit,
    int $offset,
    string $accessRequestTableNameSql
): array {
    $bindings = [];
    $whereClause = buildUserAccessWhereClause($filters, $bindings);
    $usernameStmt = $pdo->prepare(
        "SELECT dmis_username
         FROM {$tableNameSql}{$whereClause}
         GROUP BY dmis_username
         ORDER BY dmis_username ASC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($bindings as $key => $value) {
        $usernameStmt->bindValue(":" . $key, $value, PDO::PARAM_STR);
    }

    $usernameStmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $usernameStmt->bindValue(":offset", max(0, $offset), PDO::PARAM_INT);
    $usernameStmt->execute();
    $usernames = $usernameStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($usernames === []) {
        return [];
    }

    // A search narrows which users are listed, but each card still shows every access the user holds.
    $placeholders = implode(", ", array_fill(0, count($usernames), "?"));
    $accessStmt = $pdo->prepare(
        "SELECT ua.*, ar.created_at AS request_created_at
         FROM {$tableNameSql} ua
         LEFT JOIN {$accessRequestTableNameSql} ar ON ar.id = ua.access_request_id
         WHERE ua.dmis_username IN ({$placeholders})
         ORDER BY ua.dmis_username ASC, ua.module ASC"
    );
    $accessStmt->execute(array_values($usernames));

    $groups = [];
    foreach ($usernames as $username) {
        $groups[uppercaseText((string) $username)] = [
            "dmis_username" => (string) $username,
            "accesses" => [],
        ];
    }

    foreach ($accessStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $groupKey = uppercaseText((string) $row["dmis_username"]);
        if (isset($groups[$groupKey])) {
            $groups[$groupKey]["accesses"][] = $row;
        }
    }

    return array_values($groups);
}
