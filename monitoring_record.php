<?php
require __DIR__ . "/includes/auth.php";
requireMonitoringAuthentication();
require "config.php";
require __DIR__ . "/includes/monitoring_options.php";
require __DIR__ . "/includes/monitoring_helpers.php";
require __DIR__ . "/includes/monitoring_repository.php";

$company = resolveCompanyConfig($_GET["company"] ?? null, $companyConfigs);
$fixedBranch = $company["fixed_branch"] ?? null;
$showBranchSelector = $fixedBranch === null;
ensureMonitoringTable($pdo, $company);
$tableNameSql = quoteMysqlIdentifier($company["table_name"]);
$userNameSuggestions = fetchMonitoringUserNameSuggestions($pdo, $tableNameSql);
$userErrorCountsByUser = fetchMonitoringUserErrorCountsByUser($pdo, $tableNameSql);

$filterOptions = [
    "branch" => $branchOptions,
    "dealer" => $dealerOptions,
    "department" => $departmentOptions,
    "module" => $moduleOptions,
    "status" => $summaryStatusOptions,
    "action" => getMonitoringActionOptions(),
    "per_page" => $monitoringSummaryRowsPerPageOptions,
];

$filters = buildMonitoringFilters($_GET, $company, $filterOptions);
$hasIdNumberFilter = isset($_GET["id_number"]) && trim((string) $_GET["id_number"]) !== "";
$identificationNumberInput = $_GET["identification_number"] ?? $_GET["id_number"] ?? "";
$identificationNumber = normalizeIdentificationNumberFilter($identificationNumberInput);

$record = null;
if ($identificationNumber !== "") {
    $record = fetchMonitoringRecordByIdentificationNumber($pdo, $tableNameSql, $identificationNumber);
    if ($record !== null) {
        $record = enrichMonitoringRecordsWithDataCorrectionActions($pdo, $tableNameSql, [$record])[0] ?? null;
    }
}

$listQueryParams = buildMonitoringListQueryParams($company["key"], $filters, true, $monitoringSummaryRowsPerPageOptions[0]);
$backUrl = buildUrl("index.php", $listQueryParams) . "#summary-section";
$recordPageQueryParams = $listQueryParams;
if ($hasIdNumberFilter && $identificationNumber !== "") {
    $recordPageQueryParams["id_number"] = $identificationNumber;
} elseif ($identificationNumber !== "") {
    $recordPageQueryParams["identification_number"] = $identificationNumber;
}

$mitsubishiUrl = buildUrl("monitoring_record.php", $recordPageQueryParams, [
    "company" => "mitsubishi",
    "page" => 1,
]);
$hyundaiUrl = buildUrl("monitoring_record.php", $recordPageQueryParams, [
    "company" => "hyundai",
    "page" => 1,
]);

$headerKicker = $company["company_name"];
$headerTitle = "Monitoring Record Details";
$showCompanySwitch = true;
$isEditMode = $record !== null && ($_GET["edit"] ?? "") === "1";
$validationErrorMessage = resolveMonitoringValidationErrorMessage($_GET["error"] ?? null);
$today = (new DateTimeImmutable("now", new DateTimeZone("Asia/Manila")))->format("Y-m-d");
$nextMonitoringIdentificationNumber = $identificationNumber;

$recordLookupMessage = null;
if ($identificationNumber === "") {
    $recordLookupMessage = "ENTER AN ID NUMBER TO VIEW THE FULL RECORD.";
} elseif ($record === null) {
    $recordLookupMessage = "NO RECORD WAS FOUND FOR ID NUMBER " . $identificationNumber . ".";
}

$recordUserName = trim((string) ($record["user_name"] ?? ""));
$userTransactionRecords = [];
$userTransactionSummaryUrl = "";
if ($record !== null && $recordUserName !== "") {
    $userTransactionRecords = fetchMonitoringRecordsByUserName($pdo, $tableNameSql, $recordUserName);
    $userTransactionRecords = enrichMonitoringRecordsWithDataCorrectionActions($pdo, $tableNameSql, $userTransactionRecords);
    $userTransactionSummaryUrl = buildUrl("index.php", [
        "company" => $company["key"],
        "user" => $recordUserName,
    ]) . "#summary-section";
}
$memoIssuanceRecords = array_values(array_filter(
    $userTransactionRecords,
    static fn (array $historyRow): bool => isUserErrorMonitoringRecord($historyRow)
        && (
            normalizeMonitoringMemoAction((string) ($historyRow["disciplinary_action"] ?? "")) !== ""
            || isMonitoringRefresherCourseRecord($historyRow)
        )
));

$incidentReportImagePath = trim((string) ($record["incident_report_image_path"] ?? ""));
$incidentReportImageAbsolutePath = $incidentReportImagePath !== ""
    ? getMonitoringStoredFileAbsolutePath($incidentReportImagePath)
    : "";
$incidentReportImageAvailable = $incidentReportImagePath !== "" && is_file($incidentReportImageAbsolutePath);
$recordEditUrl = $record !== null
    ? buildUrl("monitoring_record.php", $recordPageQueryParams, ["edit" => 1])
    : "";
$recordViewUrl = $record !== null
    ? buildUrl("monitoring_record.php", $recordPageQueryParams, ["edit" => null])
    : "";
$recordHasPrintedMemo = $record !== null && hasPrintedMonitoringMemo($record);
$recordMemoAction = $record !== null
    ? normalizeMonitoringMemoAction((string) ($record["disciplinary_action"] ?? ""))
    : "";
$recordMemoUrl = $record !== null
    && isUserErrorMonitoringRecord($record)
    && $recordMemoAction !== ""
    ? buildUrl("export_memo_docx.php", [
        "company" => $company["key"],
        "identification_number" => $identificationNumber,
        "reprint" => $recordHasPrintedMemo ? 1 : null,
    ])
    : "";
$recordMemoLabel = $recordHasPrintedMemo ? "Reprint memo" : "Print memo";
$savedTitle = "Record Updated";
$savedMessage = $identificationNumber !== ""
    ? "Record " . $identificationNumber . " successfully updated."
    : "Record successfully updated.";

function formatMonitoringDetailDisplayValue(array $field, array $row): string
{
    $formattedValue = formatSummaryValue($field, $row);
    return trim($formattedValue) !== "" ? $formattedValue : "N/A";
}

function formatMonitoringActivityTimestamp(array $row): string
{
    $createdAt = trim((string) ($row["created_at"] ?? ""));
    $dateValue = $createdAt !== ""
        ? $createdAt
        : trim((string) ($row["transaction_date"] ?? $row["date_recorded"] ?? ""));
    $timestamp = $dateValue !== "" ? strtotime($dateValue) : false;

    if ($timestamp === false) {
        return $dateValue !== "" ? $dateValue : "Date unavailable";
    }

    return date($createdAt !== "" ? "M j, Y · g:i A" : "M j, Y", $timestamp);
}

function renderMonitoringRecordFact(string $label, string $value, string $factClass = "", bool $isMultiline = false): void
{
    $classes = trim("record-fact " . $factClass . ($isMultiline ? " record-fact-multiline" : ""));
    ?>
    <div class="<?= e($classes) ?>">
        <span class="record-fact-label"><?= e($label) ?></span>
        <div class="record-fact-value"><?= nl2br(e($value)) ?></div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($company["system_name"]) ?> Record Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="<?= e($company["logo_type"]) ?>" href="<?= e($company["logo_path"]) ?>">
    <link rel="shortcut icon" type="<?= e($company["logo_type"]) ?>" href="<?= e($company["logo_path"]) ?>">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= e(buildVersionedAssetPath("assets/css/index.css")) ?>">
</head>
<body class="company-<?= e($company["key"]) ?> page-system-monitoring page-record-details">
<?php require __DIR__ . "/includes/partials/page_header.php"; ?>

<main>
    <section class="card">

        <form action="monitoring_record.php" method="GET" class="summary-filter-form">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
            <input type="hidden" name="month_from" value="<?= e($filters["month_from"] ?? "") ?>">
            <input type="hidden" name="month_to" value="<?= e($filters["month_to"] ?? "") ?>">
            <input type="hidden" name="day" value="<?= e($filters["day"] ?? "") ?>">
            <input type="hidden" name="branch" value="<?= e($filters["branch"] ?? "") ?>">
            <input type="hidden" name="dealer" value="<?= e($filters["dealer"] ?? "") ?>">
            <input type="hidden" name="user" value="<?= e($filters["user_name"] ?? "") ?>">
            <input type="hidden" name="status" value="<?= e($filters["status"] ?? "") ?>">
            <input type="hidden" name="action" value="<?= e($filters["disciplinary_action"] ?? "") ?>">
            <?php if (!empty($filters["data_correction_only"])): ?>
            <input type="hidden" name="data_correction" value="1">
            <?php endif; ?>
            <?php if (!empty($filters["escalation_only"])): ?>
            <input type="hidden" name="escalation" value="1">
            <?php endif; ?>
            <input type="hidden" name="page" value="<?= e($filters["page"] ?? 1) ?>">

            <div class="summary-filter-grid">
                <div class="field">
                    <label for="record-identification-number">ID number</label>
                    <input
                        type="text"
                        id="record-identification-number"
                        name="id_number"
                        value="<?= e($identificationNumber) ?>"
                        placeholder="Enter ID number"
                        required
                    >
                </div>
            </div>

            <div class="summary-actions">
                <button type="submit" class="primary icon-button" aria-label="Search record" title="Search record">
                    <?= iconSvg("search") ?>
                    <span class="sr-only">Search record</span>
                </button>
            </div>
        </form>
    </section>

    <?php if ($recordLookupMessage !== null): ?>
    <section class="card">
        <div class="form-alert form-alert-error" role="alert">
            <?= e($recordLookupMessage) ?>
        </div>
    </section>
    <?php else: ?>
    <section class="card<?= !$isEditMode ? " record-information-card" : "" ?>">
        <?php if ($isEditMode): ?>
        <div class="summary-header">
            <div>
                <h2>Record Information</h2>
                <p class="note">Full incident details for ID number <strong><?= e($identificationNumber) ?></strong>.</p>
            </div>
            <div class="summary-actions">
                <a href="<?= e($recordViewUrl) ?>" class="button-link secondary icon-button" aria-label="Cancel edit" title="Cancel edit">
                    <?= iconSvg("arrow-left") ?>
                    <span class="sr-only">Cancel edit</span>
                </a>
            </div>
        </div>

        <?php
        $editingRecord = $record;
        require __DIR__ . "/includes/partials/encoding_form.php";
        ?>
        <?php else: ?>
        <?php
        $recordStatusValue = formatMonitoringDetailDisplayValue(["key" => "status", "format" => "text"], $record);
        $normalizedRecordStatus = uppercaseText($recordStatusValue);
        $recordStatusTone = in_array($normalizedRecordStatus, ["DONE", "CANCELLED", "UNPOSTED", "VOIDED", "RESOLVED"], true)
            ? "complete"
            : ($normalizedRecordStatus === "PENDING" ? "pending" : "neutral");
        ?>
        <div class="record-information-layout">
            <section class="record-details-panel record-overview-panel">
                <header class="record-panel-header record-overview-header">
                    <div>
                        <span class="record-panel-kicker">Record details</span>
                        <h2>Record information</h2>
                    </div>
                    <div class="summary-actions record-panel-actions">
                        <?php if ($recordMemoUrl !== ""): ?>
                        <a href="<?= e($recordMemoUrl) ?>" class="button-link secondary icon-button" data-memo-print-link aria-label="<?= e($recordMemoLabel) ?>" title="<?= e($recordMemoLabel) ?>">
                            <?= iconSvg("printer") ?>
                            <span class="sr-only"><?= e($recordMemoLabel) ?></span>
                        </a>
                        <?php endif; ?>
                        <a href="<?= e($recordEditUrl) ?>" class="button-link secondary icon-button" aria-label="Edit record" title="Edit record">
                            <?= iconSvg("edit") ?>
                            <span class="sr-only">Edit record</span>
                        </a>
                    </div>
                </header>

                <div class="record-panel-body">
                    <div class="record-facts-grid">
                        <?php renderMonitoringRecordFact("ID number", formatMonitoringDetailDisplayValue(["key" => "identification_number", "format" => "text"], $record)); ?>
                        <?php renderMonitoringRecordFact("Date recorded", formatMonitoringDetailDisplayValue(["key" => "date_recorded", "format" => "date"], $record)); ?>
                        <?php renderMonitoringRecordFact("Transaction date", formatMonitoringDetailDisplayValue(["key" => "transaction_date", "format" => "date"], $record)); ?>
                        <?php renderMonitoringRecordFact("Branch", formatMonitoringDetailDisplayValue(["key" => "branch", "format" => "text"], $record)); ?>
                        <?php renderMonitoringRecordFact("Dealer", formatMonitoringDetailDisplayValue(["key" => "dealer", "format" => "text"], $record)); ?>
                        <?php renderMonitoringRecordFact("Department", formatMonitoringDetailDisplayValue(["key" => "department", "format" => "text"], $record)); ?>
                        <?php renderMonitoringRecordFact("Module", formatMonitoringDetailDisplayValue(["key" => "module", "format" => "text"], $record)); ?>
                        <?php renderMonitoringRecordFact("User", formatMonitoringDetailDisplayValue(["key" => "user_name", "format" => "text"], $record)); ?>
                        <?php renderMonitoringRecordFact("Client name", formatMonitoringDetailDisplayValue(["key" => "client_name", "format" => "text"], $record)); ?>
                    </div>

                    <div class="record-panel-divider"></div>

                    <div class="record-facts-grid record-transaction-grid">
                        <?php renderMonitoringRecordFact("Transaction reference", formatMonitoringDetailDisplayValue(["key" => "invoice_reference", "format" => "text"], $record)); ?>
                        <?php renderMonitoringRecordFact("Payment reference", formatMonitoringDetailDisplayValue(["key" => "payment_reference", "format" => "text"], $record)); ?>
                        <?php renderMonitoringRecordFact("Amount", formatMonitoringDetailDisplayValue(["key" => "amount", "format" => "amount"], $record)); ?>
                    </div>

                    <div class="record-lower-details">
                        <div class="record-notes-grid">
                            <?php renderMonitoringRecordFact("Ticket", formatMonitoringDetailDisplayValue(["key" => "ticket", "format" => "text"], $record)); ?>
                            <?php renderMonitoringRecordFact("Reason", formatMonitoringDetailDisplayValue(["key" => "reason", "format" => "text"], $record), "", true); ?>
                            <?php renderMonitoringRecordFact("Remarks", formatMonitoringDetailDisplayValue(["key" => "remarks", "format" => "text"], $record), "", true); ?>
                        </div>

                        <section class="record-processing-inline" aria-labelledby="record-processing-title">
                            <div class="record-processing-inline-header">
                                <span class="record-panel-kicker">Workflow</span>
                                <h3 id="record-processing-title">Processing details</h3>
                            </div>
                            <div class="record-processing-grid">
                                <?php renderMonitoringRecordFact("Processed type", formatMonitoringDetailDisplayValue(["key" => "processed_type", "format" => "text"], $record)); ?>
                                <?php renderMonitoringRecordFact("System admin", formatMonitoringDetailDisplayValue(["key" => "system_admin", "format" => "text"], $record)); ?>
                                <?php renderMonitoringRecordFact("Approved by", formatMonitoringDetailDisplayValue(["key" => "approved_by", "format" => "text"], $record)); ?>
                                <?php renderMonitoringRecordFact("Processed by", formatMonitoringDetailDisplayValue(["key" => "processed_by", "format" => "text"], $record)); ?>
                            </div>
                        </section>
                    </div>
                </div>
            </section>

            <aside class="record-information-sidebar">
                <section class="record-details-panel record-summary-panel">
                    <header class="record-panel-header">
                        <div>
                            <span class="record-panel-kicker">Record summary</span>
                            <h2>Current status</h2>
                        </div>
                    </header>
                    <div class="record-summary-list">
                        <div class="record-summary-row record-summary-status-row">
                            <span class="record-fact-label">Status</span>
                            <span class="record-status-value record-status-value-<?= e($recordStatusTone) ?>"><?= e($recordStatusValue) ?></span>
                        </div>
                        <?php renderMonitoringRecordFact("Classification", formatMonitoringDetailDisplayValue(["key" => "classification", "format" => "text"], $record), "record-summary-row"); ?>
                        <?php renderMonitoringRecordFact("Alert", formatMonitoringDetailDisplayValue(["key" => "data_correction_alert", "format" => "text"], $record), "record-summary-row"); ?>
                        <?php renderMonitoringRecordFact("Offense", formatMonitoringDetailDisplayValue(["key" => "offense", "format" => "text"], $record), "record-summary-row"); ?>
                        <?php renderMonitoringRecordFact(
                            "Disciplinary action",
                            formatMonitoringMemoActionStatusDisplayValue($record)
                                ?: formatMonitoringDetailDisplayValue(["key" => "disciplinary_action", "format" => "text"], $record),
                            "record-summary-row"
                        ); ?>
                    </div>
                </section>

                <section class="record-details-panel record-sidebar-image-panel">
                    <header class="record-panel-header">
                        <div>
                            <span class="record-panel-kicker">Evidence</span>
                            <h2>Incident report</h2>
                        </div>
                        <?php if ($incidentReportImageAvailable): ?>
                        <a href="<?= e($incidentReportImagePath) ?>" class="button-link secondary icon-button record-sidebar-image-action" target="_blank" rel="noopener" aria-label="Open full incident report image" title="Open full image">
                            <?= iconSvg("external-link") ?>
                            <span class="sr-only">Open full image</span>
                        </a>
                        <?php endif; ?>
                    </header>
                    <div class="record-sidebar-image-body">
                        <?php if ($incidentReportImageAvailable): ?>
                        <a href="<?= e($incidentReportImagePath) ?>" class="record-evidence-preview" target="_blank" rel="noopener" aria-label="Open full incident report image">
                            <img
                                src="<?= e($incidentReportImagePath) ?>"
                                alt="Incident report image for <?= e($identificationNumber) ?>"
                            >
                        </a>
                        <?php elseif ($incidentReportImagePath !== ""): ?>
                        <div class="record-evidence-empty">Saved image unavailable.</div>
                        <?php else: ?>
                        <div class="record-evidence-empty">No image uploaded.</div>
                        <?php endif; ?>
                    </div>
                </section>
            </aside>
        </div>
        <?php endif; ?>
    </section>

    <section class="card user-transaction-history">
        <div class="summary-header user-transaction-history-header">
            <div>
                <span class="user-transaction-history-kicker">User activity</span>
                <h2>Transaction history</h2>
                <?php if ($recordUserName !== ""): ?>
                <p class="note">
                    <strong><?= e((string) count($userTransactionRecords)) ?></strong> transaction<?= count($userTransactionRecords) === 1 ? "" : "s" ?>
                    and <strong><?= e((string) count($memoIssuanceRecords)) ?></strong> memo/refresher action<?= count($memoIssuanceRecords) === 1 ? "" : "s" ?>
                    recorded for <strong><?= e(uppercaseText($recordUserName)) ?></strong>.
                </p>
                <?php else: ?>
                <p class="note">This record has no user name, so related transactions cannot be matched yet.</p>
                <?php endif; ?>
            </div>
            <?php if ($userTransactionSummaryUrl !== ""): ?>
            <a href="<?= e($userTransactionSummaryUrl) ?>" class="button-link secondary icon-button" aria-label="Open user summary" title="Open user summary">
                <?= iconSvg("search") ?>
                <span class="sr-only">Open user summary</span>
            </a>
            <?php endif; ?>
        </div>

        <?php if ($recordUserName === ""): ?>
        <p class="note">Add a user name to this record if you want it to appear in transaction history lookups.</p>
        <?php elseif ($userTransactionRecords === []): ?>
        <div class="summary-card-empty">No transactions were found for this user.</div>
        <?php else: ?>
        <ol class="transaction-activity-list">
            <?php foreach ($userTransactionRecords as $historyRow): ?>
                <?php
                $historyRecordId = (int) ($historyRow["id"] ?? 0);
                $historyIdentificationNumber = trim((string) ($historyRow["identification_number"] ?? ""));
                $historyRecordUrl = $historyIdentificationNumber !== ""
                    ? buildUrl("monitoring_record.php", $listQueryParams, [
                        "identification_number" => $historyIdentificationNumber,
                        "id_number" => null,
                    ])
                    : "";
                $isCurrentRecord = $historyRecordId === (int) ($record["id"] ?? 0);
                $historyClientName = trim((string) ($historyRow["client_name"] ?? ""));
                $historyProcessedBy = trim((string) ($historyRow["processed_by"] ?? ""));
                $historyProcessedType = trim((string) ($historyRow["processed_type"] ?? ""));
                $historyStatus = trim((string) ($historyRow["status"] ?? ""));
                $historyInvoiceReference = trim((string) ($historyRow["invoice_reference"] ?? ""));
                $historyPaymentReference = trim((string) ($historyRow["payment_reference"] ?? ""));
                $historyUserErrorCount = (int) ($historyRow["data_correction_offense_count"] ?? 0);
                $historyAlertValue = trim((string) ($historyRow["data_correction_alert"] ?? ""));
                $historySummaryParts = [];
                if ($historyClientName !== "") {
                    $historySummaryParts[] = uppercaseText($historyClientName);
                }
                if ($historyInvoiceReference !== "") {
                    $historySummaryParts[] = "Transaction ref " . $historyInvoiceReference;
                }
                if ($historyPaymentReference !== "") {
                    $historySummaryParts[] = "Payment ref " . $historyPaymentReference;
                }
                $historySummaryParts[] = formatMonitoringDetailDisplayValue(["key" => "amount", "format" => "amount"], $historyRow);
                $historyMetaParts = array_values(array_filter([
                    $historyProcessedType !== "" ? uppercaseText($historyProcessedType) : "",
                    $historyStatus !== "" ? uppercaseText($historyStatus) : "",
                    $historyUserErrorCount > 0 ? $historyUserErrorCount . " user error" . ($historyUserErrorCount === 1 ? "" : "s") : "",
                    $historyAlertValue !== "" ? uppercaseText($historyAlertValue) : "",
                ], static fn (string $value): bool => $value !== ""));
                ?>
            <li class="transaction-activity-item<?= $isCurrentRecord ? " is-current" : "" ?>">
                <span class="transaction-activity-marker" aria-hidden="true"></span>
                <div class="transaction-activity-content">
                    <div class="transaction-activity-title">
                        <?php if ($historyRecordUrl !== "" && !$isCurrentRecord): ?>
                        <a href="<?= e($historyRecordUrl) ?>"><?= e($historyIdentificationNumber) ?></a>
                        <?php else: ?>
                        <span><?= e($historyIdentificationNumber !== "" ? $historyIdentificationNumber : "Transaction") ?></span>
                        <?php endif; ?>
                        was recorded.
                    </div>
                    <div class="transaction-activity-summary"><?= e(implode(" · ", $historySummaryParts)) ?></div>
                    <div class="transaction-activity-byline">
                        By <?= e($historyProcessedBy !== "" ? uppercaseText($historyProcessedBy) : "System Administrator") ?>
                        <?php if ($historyMetaParts !== []): ?>
                        <span aria-hidden="true">·</span> <?= e(implode(" · ", $historyMetaParts)) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="transaction-activity-time">
                    <time datetime="<?= e((string) ($historyRow["created_at"] ?? $historyRow["transaction_date"] ?? "")) ?>"><?= e(formatMonitoringActivityTimestamp($historyRow)) ?></time>
                    <?php if ($isCurrentRecord): ?>
                    <span class="transaction-activity-current"></span>
                    <?php elseif ($historyRecordUrl !== ""): ?>
                    <a href="<?= e($historyRecordUrl) ?>">Open record</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>

        <?php require __DIR__ . "/includes/partials/memo_issuance_history.php"; ?>
    </section>

<?php endif; ?>
</main>

<?php if (isset($_GET["updated"])): ?>
    <?php require __DIR__ . "/includes/partials/saved_modal.php"; ?>
<?php endif; ?>

<script src="<?= e(buildVersionedAssetPath("assets/js/index.js")) ?>" defer></script>
</body>
</html>
