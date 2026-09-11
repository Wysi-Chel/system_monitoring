<?php
const ACCESS_REQUEST_CSRF_SESSION_KEY = "system_monitoring_access_request_csrf";
const ACCESS_REQUEST_SUBMITTED_AT_SESSION_KEY = "system_monitoring_access_request_submitted_at";
const ACCESS_REQUEST_SUBMIT_COOLDOWN_SECONDS = 45;
const ACCESS_REQUEST_NAME_MAX_LENGTH = 150;
const ACCESS_REQUEST_USERNAME_MAX_LENGTH = 100;
const ACCESS_REQUEST_DESCRIPTION_MAX_LENGTH = 2000;

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
    $portalUser = $_SESSION[MONITORING_PORTAL_SESSION_USER_KEY] ?? [];
    if (!is_array($portalUser)) {
        $portalUser = [];
    }

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
            module,
            description,
            status,
            submitted_ip
        ) VALUES (
            :reference_no,
            :requester_name,
            :dealer,
            :department,
            :dmis_username,
            :module,
            :description,
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
                ":module" => $values["module"],
                ":description" => $values["description"],
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
            "module",
            "department",
            "description",
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

function fetchAccessRequestById(PDO $pdo, string $tableNameSql, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM {$tableNameSql} WHERE id = :id LIMIT 1");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record === false ? null : $record;
}

function updateAccessRequestReview(
    PDO $pdo,
    string $tableNameSql,
    int $id,
    string $status,
    ?string $reviewNotes,
    string $reviewedBy
): void {
    $reviewedAt = (new DateTimeImmutable("now", new DateTimeZone("Asia/Manila")))->format("Y-m-d H:i:s");
    $stmt = $pdo->prepare(
        "UPDATE {$tableNameSql}
         SET status = :status,
             review_notes = :review_notes,
             reviewed_by = :reviewed_by,
             reviewed_at = :reviewed_at
         WHERE id = :id"
    );
    $stmt->bindValue(":status", $status, PDO::PARAM_STR);
    $stmt->bindValue(":review_notes", $reviewNotes, $reviewNotes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":reviewed_by", $reviewedBy, PDO::PARAM_STR);
    $stmt->bindValue(":reviewed_at", $reviewedAt, PDO::PARAM_STR);
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
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
