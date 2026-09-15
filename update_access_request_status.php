<?php
require __DIR__ . "/includes/auth.php";
requireMonitoringAuthentication();
require "config.php";
require __DIR__ . "/includes/monitoring_options.php";
require __DIR__ . "/includes/monitoring_helpers.php";
require __DIR__ . "/includes/monitoring_repository.php";
require __DIR__ . "/includes/access_request_repository.php";

$company = resolveCompanyConfig($_POST["company"] ?? $_GET["company"] ?? null, $companyConfigs);
ensureAccessRequestTable($pdo, $company);

$accessRequestTableNameSql = quoteMysqlIdentifier($company["access_request_table_name"]);
$accessRequestsUrl = buildUrl("access_requests.php", ["company" => $company["key"]]);
$requestId = normalizePositiveInt($_POST["id"] ?? 0, 0);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST" || $requestId === 0) {
    header("Location: " . $accessRequestsUrl);
    exit;
}

$record = fetchAccessRequestById($pdo, $accessRequestTableNameSql, $requestId);
if ($record === null) {
    header("Location: " . $accessRequestsUrl);
    exit;
}

$reviewType = in_array($_POST["review_type"] ?? "", ["it", "final", "implementation"], true) ? $_POST["review_type"] : "it";
$redirectParams = [
    "company" => $company["key"],
    "id" => $requestId,
];
$errorCode = "";

if (!isValidAccessRequestCsrfToken($_POST["csrf_token"] ?? null)) {
    $errorCode = "session_expired";
} elseif ($reviewType === "it") {
    $grantAccess = buildAccessRequestGrantAccess($_POST, $moduleOptions);
    $itNotes = trim((string) ($_POST["it_notes"] ?? ""));
    $hasLongAccessDetails = array_filter(
        $grantAccess,
        static fn(string $details): bool => getAccessRequestTextLength($details) > ACCESS_REQUEST_ACCESS_DETAILS_MAX_LENGTH
    ) !== [];

    if (!isAccessRequestItReviewer()) {
        $errorCode = "not_permitted";
    } elseif (!canSubmitAccessRequestItReview($record)) {
        $errorCode = "it_locked";
    } elseif ($grantAccess === []) {
        $errorCode = "grant_access_required";
    } elseif ($hasLongAccessDetails || getAccessRequestTextLength($itNotes) > ACCESS_REQUEST_REVIEW_NOTES_MAX_LENGTH) {
        $errorCode = "review_too_long";
    } else {
        submitAccessRequestItReview(
            $pdo,
            $accessRequestTableNameSql,
            $requestId,
            $grantAccess,
            $itNotes !== "" ? $itNotes : null,
            getAccessRequestPortalUserName()
        );
    }
} elseif ($reviewType === "final") {
    $grantAccess = decodeAccessRequestGrantAccess($record["grant_access"] ?? null);
    $approvedAccess = buildAccessRequestApprovedAccess($_POST, $grantAccess);
    $finalNotes = trim((string) ($_POST["final_notes"] ?? ""));

    if (!isAccessRequestFinalReviewer()) {
        $errorCode = "not_permitted";
    } elseif (!canSaveAccessRequestFinalReview($record)) {
        $errorCode = "final_unavailable";
    } elseif (count($approvedAccess) < count($grantAccess) && $finalNotes === "") {
        $errorCode = "final_notes_required";
    } elseif (getAccessRequestTextLength($finalNotes) > ACCESS_REQUEST_REVIEW_NOTES_MAX_LENGTH) {
        $errorCode = "review_too_long";
    } elseif (!saveAccessRequestFinalReview(
        $pdo,
        $accessRequestTableNameSql,
        $requestId,
        $approvedAccess,
        $finalNotes !== "" ? $finalNotes : null,
        getAccessRequestPortalUserName(),
        trim((string) ($_POST["it_reviewed_at"] ?? ""))
    )) {
        $errorCode = "review_changed";
    }
} else {
    $implementationNotes = mb_strtoupper(trim((string) ($_POST["implementation_notes"] ?? "")), 'UTF-8');

    if (!isAccessRequestItReviewer()) {
        $errorCode = "not_permitted";
    } elseif (!canMarkAccessRequestImplemented($record)) {
        $errorCode = "implementation_unavailable";
    } elseif (getAccessRequestTextLength($implementationNotes) > ACCESS_REQUEST_REVIEW_NOTES_MAX_LENGTH) {
        $errorCode = "review_too_long";
    } elseif (!markAccessRequestImplemented(
        $pdo,
        $company,
        $requestId,
        $implementationNotes !== "" ? $implementationNotes : null,
        getAccessRequestPortalUserName()
    )) {
        $errorCode = "implementation_unavailable";
    }
}

if ($errorCode !== "") {
    $redirectParams["error"] = $errorCode;
    $redirectParams["review"] = $reviewType;
} else {
    $redirectParams["saved"] = $reviewType;
}

header("Location: access_request_view.php?" . http_build_query($redirectParams) . "#access-request-" . $reviewType . "-review");
exit;
