<?php
require __DIR__ . "/includes/auth.php";
requireMonitoringAuthentication();
require "config.php";
require __DIR__ . "/includes/monitoring_options.php";
require __DIR__ . "/includes/monitoring_helpers.php";
require __DIR__ . "/includes/monitoring_repository.php";
require __DIR__ . "/includes/access_request_repository.php";

$company = resolveCompanyConfig($_GET["company"] ?? null, $companyConfigs);
ensureAccessRequestTable($pdo, $company);

$accessRequestTableNameSql = quoteMysqlIdentifier($company["access_request_table_name"]);
$requestId = normalizePositiveInt($_GET["id"] ?? 0, 0);
$record = $requestId > 0 ? fetchAccessRequestById($pdo, $accessRequestTableNameSql, $requestId) : null;
if ($record === null) {
    http_response_code(404);
}

$accessRequestsUrl = buildUrl("access_requests.php", ["company" => $company["key"]]) . "#access-request-summary";
$mitsubishiUrl = buildUrl("access_requests.php", ["company" => "mitsubishi"]);
$hyundaiUrl = buildUrl("access_requests.php", ["company" => "hyundai"]);
$headerKicker = $company["company_name"];
$headerTitle = $record !== null ? (string) $record["reference_no"] : "Access Request";
$showCompanySwitch = true;
$csrfToken = getAccessRequestCsrfToken();
$recordStatus = trim((string) ($record["status"] ?? ""));
$reviewErrorMessages = [
    "session_expired" => "Your session token expired. Refresh the page and save the review again.",
    "invalid_status" => "Select a valid review status.",
];
$reviewErrorMessage = $reviewErrorMessages[(string) ($_GET["error"] ?? "")] ?? "";
$savedTitle = "Review Saved";
$savedMessage = $record !== null
    ? "Access request " . $record["reference_no"] . " is now " . $recordStatus . "."
    : "Access request review saved.";
$detailFields = $record === null ? [] : [
    ["label" => "Requestor", "value" => $record["requester_name"]],
    ["label" => "DMIS username", "value" => $record["dmis_username"]],
    ["label" => "Dealer", "value" => $record["dealer"]],
    ["label" => "Department", "value" => $record["department"]],
    ["label" => "Module", "value" => $record["module"]],
    ["label" => "Submitted", "value" => formatDisplayTimestamp($record["created_at"])],
    ["label" => "Submitted IP", "value" => $record["submitted_ip"]],
    ["label" => "Reviewed by", "value" => $record["reviewed_by"]],
    ["label" => "Reviewed at", "value" => formatDisplayTimestamp($record["reviewed_at"])],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($headerTitle) ?> · <?= e($company["company_name"]) ?> Access Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="<?= e($company["logo_type"]) ?>" href="<?= e($company["logo_path"]) ?>">
    <link rel="shortcut icon" type="<?= e($company["logo_type"]) ?>" href="<?= e($company["logo_path"]) ?>">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= e(buildVersionedAssetPath("assets/css/index.css")) ?>">
</head>
<body class="company-<?= e($company["key"]) ?> page-ticket-monitoring page-access-requests">
<?php require __DIR__ . "/includes/partials/page_header.php"; ?>

<main>
    <?php if ($record === null): ?>
    <section class="card">
        <div class="summary-header">
            <div>
                <h2>Access request not found</h2>
            </div>
            <a href="<?= e($accessRequestsUrl) ?>" class="button-link secondary icon-button" aria-label="Back to access requests" title="Back to access requests">
                <?= iconSvg("arrow-left") ?>
                <span class="sr-only">Back to access requests</span>
            </a>
        </div>
        <div class="summary-card-empty">No access request was found in the <?= e($company["company_name"]) ?> workspace.</div>
    </section>
    <?php else: ?>
    <section class="card">
        <div class="summary-header">
            <div>
                <span class="dashboard-activity-id"><?= e($record["reference_no"]) ?></span>
                <h2><?= e($record["requester_name"]) ?></h2>
            </div>
            <div class="summary-card-action">
                <span class="status-pill status-pill-<?= e(getAccessRequestStatusClass($recordStatus)) ?>"><?= e($recordStatus !== "" ? $recordStatus : "Unknown") ?></span>
                <a href="<?= e($accessRequestsUrl) ?>" class="button-link secondary icon-button" aria-label="Back to access requests" title="Back to access requests">
                    <?= iconSvg("arrow-left") ?>
                    <span class="sr-only">Back to access requests</span>
                </a>
            </div>
        </div>

        <div class="summary-card-grid">
            <?php foreach ($detailFields as $detailField): ?>
            <div class="summary-card-field">
                <div class="summary-card-label"><?= e($detailField["label"]) ?></div>
                <div class="summary-card-value"><?= e(trim((string) $detailField["value"]) !== "" ? $detailField["value"] : "N/A") ?></div>
            </div>
            <?php endforeach; ?>
            <div class="summary-card-field summary-card-field-full">
                <div class="summary-card-label">Description</div>
                <div class="summary-card-value summary-card-value-multiline"><?= nl2br(e($record["description"])) ?></div>
            </div>
            <?php if (trim((string) ($record["review_notes"] ?? "")) !== ""): ?>
            <div class="summary-card-field summary-card-field-full">
                <div class="summary-card-label">Review notes</div>
                <div class="summary-card-value summary-card-value-multiline"><?= nl2br(e($record["review_notes"])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </section>
 <section class="card" id="access-request-review">
        <div class="summary-header">
            <div>
                <h2>IT Review</h2>
            </div>
        </div>

        <?php if ($reviewErrorMessage !== ""): ?>
        <div class="form-alert form-alert-error" role="alert"><?= e($reviewErrorMessage) ?></div>
        <?php endif; ?>

        <form action="update_access_request_status.php" method="POST" class="access-request-review-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
            <input type="hidden" name="id" value="<?= e($record["id"]) ?>">

            <div class="access-request-review-grid">
                <div class="field">
                    <label for="access-review-status">Status</label>
                    <select id="access-review-status" name="status" required>
                        <?php foreach ($accessRequestStatusOptions as $option): ?>
                        <option value="<?= e($option) ?>"<?= $recordStatus === $option ? " selected" : "" ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="access-review-notes">Review notes</label>
                    <textarea id="access-review-notes" name="review_notes" rows="4" placeholder="Approval scope, decline reason, or follow-up notes"><?= e($record["review_notes"] ?? "") ?></textarea>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="primary button-with-icon">
                    <?= iconSvg("save") ?>
                    <span>Save review</span>
                </button>
            </div>
        </form>
    </section>
    <section class="card" id="access-request-review">
        <div class="summary-header">
            <div>
                <h2>Final Review</h2>
            </div>
        </div>

        <?php if ($reviewErrorMessage !== ""): ?>
        <div class="form-alert form-alert-error" role="alert"><?= e($reviewErrorMessage) ?></div>
        <?php endif; ?>

        <form action="update_access_request_status.php" method="POST" class="access-request-review-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
            <input type="hidden" name="id" value="<?= e($record["id"]) ?>">

            <div class="access-request-review-grid">
                <div class="field">
                    <label for="access-review-status">Status</label>
                    <select id="access-review-status" name="status" required>
                        <?php foreach ($accessRequestStatusOptions as $option): ?>
                        <option value="<?= e($option) ?>"<?= $recordStatus === $option ? " selected" : "" ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="access-review-notes">Review notes</label>
                    <textarea id="access-review-notes" name="review_notes" rows="4" placeholder="Approval scope, decline reason, or follow-up notes"><?= e($record["review_notes"] ?? "") ?></textarea>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="primary button-with-icon">
                    <?= iconSvg("save") ?>
                    <span>Save review</span>
                </button>
            </div>
        </form>
    </section>
    <?php endif; ?>
</main>

<?php if ($record !== null && isset($_GET["saved"])): ?>
    <?php require __DIR__ . "/includes/partials/saved_modal.php"; ?>
<?php endif; ?>

<script src="assets/js/index.js" defer></script>
</body>
</html>
