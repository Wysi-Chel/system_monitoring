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

$redirectParams = [
    "company" => $company["key"],
    "id" => $requestId,
];

if (!isValidAccessRequestCsrfToken($_POST["csrf_token"] ?? null)) {
    $redirectParams["error"] = "session_expired";
} else {
    $status = normalizeAllowedFilter($_POST["status"] ?? "", $accessRequestStatusOptions);
    $reviewNotes = trim((string) ($_POST["review_notes"] ?? ""));

    if ($status === "") {
        $redirectParams["error"] = "invalid_status";
    } else {
        updateAccessRequestReview(
            $pdo,
            $accessRequestTableNameSql,
            $requestId,
            $status,
            $reviewNotes !== "" ? $reviewNotes : null,
            getAccessRequestPortalUserName()
        );
        $redirectParams["saved"] = 1;
    }
}

header("Location: access_request_view.php?" . http_build_query($redirectParams));
exit;
?>
