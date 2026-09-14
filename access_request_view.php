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
    "session_expired" => "Your session token expired. Refresh the page and save again.",
    "grant_access_required" => "Tick at least one module to request for this user.",
    "it_locked" => "The access can no longer be changed because the final review approved it.",
    "invalid_final_decision" => "Select Approve or Decline for the final review.",
    "decline_notes_required" => "Add notes explaining why the access is declined.",
    "final_unavailable" => "The final review is only open while the request is waiting for approval.",
    "review_changed" => "IT changed the requested access after you opened this page. Check it again before deciding.",
    "implementation_unavailable" => "Only approved requests can be marked as implemented.",
    "review_too_long" => "Shorten the access details or notes and save again.",
];
$reviewErrorMessage = $reviewErrorMessages[(string) ($_GET["error"] ?? "")] ?? "";
$reviewErrorSection = in_array($_GET["review"] ?? "", ["it", "final", "implementation"], true) ? $_GET["review"] : "it";
$savedTitles = [
    "it" => "Sent For Final Review",
    "final" => "Final Review Saved",
    "implementation" => "Marked As Implemented",
];
$savedTitle = $savedTitles[(string) ($_GET["saved"] ?? "")] ?? "Access Request Saved";
$savedMessage = $record !== null
    ? "Request pending for approval."
    : "Access request saved.";

$requestedModules = splitMultiValueText($record["module"] ?? "");
$grantAccess = decodeAccessRequestGrantAccess($record["grant_access"] ?? null);
$accessComparisonRows = buildAccessRequestComparisonRows($requestedModules, $grantAccess);
$itNotes = trim((string) ($record["it_notes"] ?? ""));
$finalDecision = trim((string) ($record["final_decision"] ?? ""));
$finalNotes = trim((string) ($record["final_notes"] ?? ""));
$implementationNotes = trim((string) ($record["implementation_notes"] ?? ""));
$hasItReview = trim((string) ($record["it_reviewed_at"] ?? "")) !== "";
// A Pending request can still hold an IT draft saved before IT reviews were sent for approval.
$isItReviewSent = $hasItReview && $recordStatus !== "Pending";
$hasFinalReview = trim((string) ($record["final_reviewed_at"] ?? "")) !== "";
$isImplemented = trim((string) ($record["implemented_at"] ?? "")) !== "";
// Until IT saves access, start from the modules the requester asked for.
$grantSelectedModules = $hasItReview ? array_keys($grantAccess) : $requestedModules;
$canSubmitItReview = $record !== null && canSubmitAccessRequestItReview($record);
$canSaveFinalReview = $record !== null && canSaveAccessRequestFinalReview($record);
$canMarkImplemented = $record !== null && canMarkAccessRequestImplemented($record);
$finalDecisionLabels = [
    "Approved" => "Approve",
    "Declined" => "Decline",
];

if ($finalDecision !== "") {
    $finalStatusLabel = $finalDecision;
} elseif ($recordStatus === "For Approval") {
    $finalStatusLabel = "For Approval";
} else {
    $finalStatusLabel = "Awaiting IT";
}

$userAccesses = $record !== null
    ? fetchUserAccessesByUsername($pdo, quoteMysqlIdentifier($company["user_access_table_name"]), (string) $record["dmis_username"])
    : [];
$userAccessesUrl = buildUrl("access_requests.php", [
    "company" => $company["key"],
    "view" => "accesses",
    "q" => (string) ($record["dmis_username"] ?? ""),
]) . "#access-request-summary";

$detailFields = $record === null ? [] : [
    ["label" => "Requestor", "value" => $record["requester_name"]],
    ["label" => "DMIS username", "value" => $record["dmis_username"]],
    ["label" => "Dealer", "value" => $record["dealer"]],
    ["label" => "Department", "value" => $record["department"]],
    ["label" => "Modules requested", "value" => implode(", ", $requestedModules)],
    ["label" => "Requested by", "value" => $record["requested_by"] ?? ""],
    ["label" => "Submitted", "value" => formatDisplayTimestamp($record["created_at"])],
    ["label" => "Submitted IP", "value" => $record["submitted_ip"]],
];

$accessChangeTags = [
    "added" => ["label" => "Added by IT", "class" => "ticket"],
    "not_included" => ["label" => "Not included", "class" => "alert"],
];
$renderAccessList = static function (array $rows) use ($accessChangeTags): void {
    if ($rows === []) {
        echo '<p class="dashboard-empty-state">IT has not selected any access yet.</p>';
        return;
    }

    echo '<ul class="access-list">';
    foreach ($rows as $row) {
        $changeTag = $accessChangeTags[$row["change"]] ?? null;
        if ($row["change"] === "not_included") {
            $detailsText = "Requested by the user";
        } else {
            $detailsText = $row["details"] !== "" ? $row["details"] : "No access details added";
        }

        echo '<li class="access-list-item">';
        echo '<span class="access-list-module">' . e($row["module"]) . '</span>';
        echo '<span class="access-list-details">' . e($detailsText) . '</span>';
        echo '<span class="access-list-tag">';
        if ($changeTag !== null) {
            echo '<span class="dashboard-chip ' . e($changeTag["class"]) . '">' . e($changeTag["label"]) . '</span>';
        }
        echo '</span>';
        echo '</li>';
    }
    echo '</ul>';
};
$renderReviewNotes = static function (string $label, string $notes): void {
    if ($notes === "") {
        return;
    }

    echo '<div class="summary-card-field summary-card-field-full access-review-notes">';
    echo '<div class="summary-card-label">' . e($label) . '</div>';
    echo '<div class="summary-card-value summary-card-value-multiline">' . nl2br(e($notes)) . '</div>';
    echo '</div>';
};
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
                <div class="summary-card-label">Earlier review notes</div>
                <div class="summary-card-value summary-card-value-multiline"><?= nl2br(e($record["review_notes"])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card" id="access-request-it-review">
        <div class="summary-header">
            <div>
                <h2>IT Review</h2>
                <?php if ($hasItReview): ?>
                <div class="dashboard-activity-meta"><?= $isItReviewSent ? "Sent for final review" : "Draft saved" ?> by <?= e($record["it_reviewed_by"]) ?> on <?= e(formatDisplayTimestamp($record["it_reviewed_at"])) ?></div>
                <?php endif; ?>
            </div>
            <div class="summary-card-action">
                <span class="status-pill status-pill-<?= $isItReviewSent ? "submitted" : "pending" ?>"><?= $isItReviewSent ? "Submitted" : "Pending" ?></span>
            </div>
        </div>

        <?php if ($reviewErrorMessage !== "" && $reviewErrorSection === "it"): ?>
        <div class="form-alert form-alert-error" role="alert"><?= e($reviewErrorMessage) ?></div>
        <?php endif; ?>

        <?php if ($canSubmitItReview): ?>
        <?php if ($recordStatus === "Declined"): ?>
        <p class="field-note access-review-hint">The final review declined this access. Revise it and send it again.</p>
        <?php endif; ?>
        <form action="update_access_request_status.php" method="POST" class="access-request-review-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
            <input type="hidden" name="id" value="<?= e($record["id"]) ?>">
            <input type="hidden" name="review_type" value="it">

            <div class="field">
                <label id="access-grant-modules-label">Access to grant</label>
                <p class="field-note">Tick each module this user should get and describe the access. Sending it opens the final review.</p>
                <div class="access-module-list" role="group" aria-labelledby="access-grant-modules-label">
                    <?php foreach ($moduleOptions as $moduleIndex => $moduleOption): ?>
                    <?php $moduleFieldId = "access-grant-module-" . $moduleIndex; ?>
                    <div class="access-module-row">
                        <label class="option-button" for="<?= e($moduleFieldId) ?>">
                            <input type="checkbox" id="<?= e($moduleFieldId) ?>" name="grant_modules[]" value="<?= e($moduleOption) ?>"<?= in_array($moduleOption, $grantSelectedModules, true) ? " checked" : "" ?>>
                            <span><?= e($moduleOption) ?></span>
                        </label>
                        <input type="text" name="grant_access[<?= e($moduleOption) ?>]" value="<?= e($grantAccess[$moduleOption] ?? "") ?>" maxlength="<?= e(ACCESS_REQUEST_ACCESS_DETAILS_MAX_LENGTH) ?>" placeholder="Access, role, or restriction" aria-label="<?= e($moduleOption) ?> access details">
                        <span class="access-list-tag">
                            <?php if (in_array($moduleOption, $requestedModules, true)): ?>
                            <span class="dashboard-chip">Requested</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="field access-review-notes-field">
                <label for="access-it-notes">IT notes</label>
                <textarea id="access-it-notes" name="it_notes" rows="4" maxlength="<?= e(ACCESS_REQUEST_REVIEW_NOTES_MAX_LENGTH) ?>" placeholder="Changes from the request or reasons for the access"><?= e($itNotes) ?></textarea>
            </div>

            <div class="buttons">
                <button type="submit" class="primary button-with-icon">
                    <?= iconSvg("send") ?>
                    <span><?= $isItReviewSent ? "Send again for final review" : "Send for final review" ?></span>
                </button>
            </div>
        </form>
        <?php else: ?>
        <?php $renderAccessList($accessComparisonRows); ?>
        <?php $renderReviewNotes("IT notes", $itNotes); ?>
        <?php endif; ?>
    </section>

    <section class="card" id="access-request-final-review">
        <div class="summary-header">
            <div>
                <h2>Final Review</h2>
                <?php if ($hasFinalReview && $finalDecision !== ""): ?>
                <div class="dashboard-activity-meta"><?= e($finalDecision) ?> by <?= e($record["final_reviewed_by"]) ?> on <?= e(formatDisplayTimestamp($record["final_reviewed_at"])) ?></div>
                <?php elseif ($hasFinalReview): ?>
                <div class="dashboard-activity-meta">Reviewed by <?= e($record["final_reviewed_by"]) ?> on <?= e(formatDisplayTimestamp($record["final_reviewed_at"])) ?>, then sent again by IT</div>
                <?php endif; ?>
            </div>
            <div class="summary-card-action">
                <span class="status-pill status-pill-<?= e(getAccessRequestStatusClass($finalStatusLabel)) ?>"><?= e($finalStatusLabel) ?></span>
            </div>
        </div>

        <?php if ($reviewErrorMessage !== "" && $reviewErrorSection === "final"): ?>
        <div class="form-alert form-alert-error" role="alert"><?= e($reviewErrorMessage) ?></div>
        <?php endif; ?>

        <?php if ($canSaveFinalReview): ?>
        <div class="field">
            <label>Access requested by IT</label>
            <?php $renderAccessList($accessComparisonRows); ?>
        </div>
        <?php $renderReviewNotes("IT notes", $itNotes); ?>

        <form action="update_access_request_status.php" method="POST" class="access-request-review-form" data-final-review-form>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
            <input type="hidden" name="id" value="<?= e($record["id"]) ?>">
            <input type="hidden" name="review_type" value="final">
            <input type="hidden" name="it_reviewed_at" value="<?= e($record["it_reviewed_at"]) ?>">

            <div class="access-request-review-grid">
                <div class="field">
                    <label id="access-final-decision-label">Decision</label>
                    <div class="option-group" role="radiogroup" aria-labelledby="access-final-decision-label">
                        <?php foreach ($accessRequestFinalDecisionOptions as $optionIndex => $option): ?>
                        <label class="option-button" for="access-final-decision-<?= e($optionIndex) ?>">
                            <input type="radio" id="access-final-decision-<?= e($optionIndex) ?>" name="final_decision" value="<?= e($option) ?>" required>
                            <span><?= e($finalDecisionLabels[$option] ?? $option) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="field">
                    <label for="access-final-notes">Notes</label>
                    <textarea id="access-final-notes" name="final_notes" rows="4" maxlength="<?= e(ACCESS_REQUEST_REVIEW_NOTES_MAX_LENGTH) ?>" placeholder="Approval remarks, or the reason for declining (required when declining)"><?= e($finalNotes) ?></textarea>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="primary button-with-icon">
                    <?= iconSvg("check") ?>
                    <span>Save final review</span>
                </button>
            </div>
        </form>
        <?php elseif ($finalDecision !== ""): ?>
        <?php if ($finalDecision === "Declined"): ?>
        <p class="field-note access-review-hint">IT can revise the access in the IT review and send it again.</p>
        <?php endif; ?>
        <?php $renderReviewNotes("Final review notes", $finalNotes); ?>
        <?php else: ?>
        <div class="summary-card-empty">The final review opens once IT sends the access to grant.</div>
        <?php endif; ?>
    </section>

    <section class="card" id="access-request-implementation-review">
        <div class="summary-header">
            <div>
                <h2>Implementation</h2>
                <?php if ($isImplemented): ?>
                <div class="dashboard-activity-meta">Implemented by <?= e($record["implemented_by"]) ?> on <?= e(formatDisplayTimestamp($record["implemented_at"])) ?></div>
                <?php endif; ?>
            </div>
            <div class="summary-card-action">
                <span class="status-pill status-pill-<?= $isImplemented ? "implemented" : "pending" ?>"><?= $isImplemented ? "Implemented" : "Pending" ?></span>
            </div>
        </div>

        <?php if ($reviewErrorMessage !== "" && $reviewErrorSection === "implementation"): ?>
        <div class="form-alert form-alert-error" role="alert"><?= e($reviewErrorMessage) ?></div>
        <?php endif; ?>

        <?php if ($canMarkImplemented): ?>
        <p class="field-note access-review-hint">Set up the approved access shown in the IT review, then mark it as implemented. The accesses are recorded for this user once marked.</p>
        <form action="update_access_request_status.php" method="POST" class="access-request-review-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
            <input type="hidden" name="id" value="<?= e($record["id"]) ?>">
            <input type="hidden" name="review_type" value="implementation">

            <div class="field access-review-notes-field">
                <label for="access-implementation-notes">Implementation notes</label>
                <textarea id="access-implementation-notes" name="implementation_notes" rows="3" maxlength="<?= e(ACCESS_REQUEST_REVIEW_NOTES_MAX_LENGTH) ?>" placeholder="Account changes made, effective date, or follow-up"><?= e($implementationNotes) ?></textarea>
            </div>

            <div class="buttons">
                <button type="submit" class="primary button-with-icon">
                    <?= iconSvg("check") ?>
                    <span>Mark as implemented</span>
                </button>
            </div>
        </form>
        <?php elseif ($isImplemented): ?>
        <p class="field-note access-review-hint">The approved accesses are recorded under this user's current accesses.</p>
        <?php $renderReviewNotes("Implementation notes", $implementationNotes); ?>
        <?php else: ?>
        <div class="summary-card-empty">Implementation opens once the final review approves the access.</div>
        <?php endif; ?>
    </section>

    <section class="card" id="access-request-user-accesses">
        <div class="summary-header">
            <div>
                <h2>Current Accesses</h2>
                <div class="dashboard-activity-meta">DMIS username: <?= e($record["dmis_username"]) ?></div>
            </div>
            <a href="<?= e($userAccessesUrl) ?>" class="button-link secondary icon-button" aria-label="Open user accesses" title="Open user accesses">
                <?= iconSvg("users") ?>
                <span class="sr-only">Open user accesses</span>
            </a>
        </div>

        <?php if ($userAccesses === []): ?>
        <div class="summary-card-empty">No implemented accesses are recorded for this user yet.</div>
        <?php else: ?>
        <ul class="access-list">
            <?php foreach ($userAccesses as $userAccess): ?>
            <?php $accessDetails = mb_strtoupper(trim((string) ($userAccess["access_details"] ?? "")), 'UTF-8'); ?>
            <li class="access-list-item">
                <span class="access-list-module"><?= e($userAccess["module"]) ?></span>
                <span class="access-list-details"><?= e($accessDetails !== "" ? $accessDetails : "Access granted") ?></span>
                <a href="<?= e(buildUrl("access_request_view.php", ["company" => $company["key"], "id" => (int) $userAccess["access_request_id"]])) ?>" class="access-list-source" title="Approved by <?= e($userAccess["approved_by"]) ?>, implemented by <?= e($userAccess["implemented_by"]) ?>"><?= e($userAccess["reference_no"]) ?> · <?= e(formatDisplayDate($userAccess["granted_at"])) ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</main>

<?php if ($record !== null && isset($_GET["saved"])): ?>
    <?php require __DIR__ . "/includes/partials/saved_modal.php"; ?>
<?php endif; ?>

<script>
document.querySelectorAll("[data-final-review-form]").forEach(function (form) {
    var notes = form.querySelector("[name='final_notes']");
    form.addEventListener("change", function (event) {
        if (event.target.name === "final_decision") {
            notes.required = event.target.value === "Declined";
        }
    });
});
</script>
<script src="assets/js/index.js" defer></script>
</body>
</html>
