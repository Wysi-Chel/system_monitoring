<?php
$paginationPages = buildPaginationPages($pagination["page"], $pagination["total_pages"]);
$summaryAnchor = "#summary-section";
$monitoringActionOptions = getMonitoringActionOptions();
$monitoringDoneStatus = getMonitoringDoneStatus();
$monitoringIncidentReportResolvedAction = getMonitoringIncidentReportResolvedAction();
$userNameSuggestions = isset($userNameSuggestions) && is_array($userNameSuggestions) ? $userNameSuggestions : [];
$formatCardValue = static function (string $value): string {
    $value = trim($value);
    return $value !== "" ? $value : "N/A";
};
$renderMonthPicker = static function (string $id, string $name, string $label, string $value): void {
    $normalizedValue = normalizeMonthFilter($value);
    $selectedDate = $normalizedValue !== ""
        ? DateTimeImmutable::createFromFormat("!Y-m", $normalizedValue)
        : false;
    $currentDate = new DateTimeImmutable("now", new DateTimeZone("Asia/Manila"));
    $displayYear = $selectedDate instanceof DateTimeImmutable
        ? (int) $selectedDate->format("Y")
        : (int) $currentDate->format("Y");
    $displayLabel = $selectedDate instanceof DateTimeImmutable
        ? $selectedDate->format("F Y")
        : "Select month";
    $monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    $popoverId = $id . "-popover";
    ?>
    <div class="field month-picker" data-month-picker data-display-year="<?= e($displayYear) ?>">
        <label for="<?= e($id) ?>-trigger"><?= e($label) ?></label>
        <input type="hidden" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($normalizedValue) ?>" data-month-picker-input>
        <button
            type="button"
            id="<?= e($id) ?>-trigger"
            class="month-picker-trigger"
            aria-haspopup="dialog"
            aria-expanded="false"
            aria-controls="<?= e($popoverId) ?>"
            data-month-picker-trigger
        >
            <span data-month-picker-label><?= e($displayLabel) ?></span>
            <?= iconSvg("calendar") ?>
        </button>
        <div class="month-picker-popover" id="<?= e($popoverId) ?>" role="dialog" aria-label="<?= e($label) ?>" data-month-picker-popover hidden>
            <div class="month-picker-header">
                <button type="button" class="month-picker-year-button" aria-label="Previous year" data-month-picker-previous>
                    <?= iconSvg("arrow-left") ?>
                </button>
                <strong data-month-picker-year><?= e($displayYear) ?></strong>
                <button type="button" class="month-picker-year-button" aria-label="Next year" data-month-picker-next>
                    <?= iconSvg("arrow-right") ?>
                </button>
            </div>
            <div class="month-picker-grid" role="group" aria-label="Months">
                <?php foreach ($monthNames as $monthIndex => $monthName): ?>
                <?php $monthValue = str_pad((string) ($monthIndex + 1), 2, "0", STR_PAD_LEFT); ?>
                <button
                    type="button"
                    class="month-picker-month"
                    data-month-picker-month="<?= e($monthValue) ?>"
                    aria-pressed="<?= $selectedDate instanceof DateTimeImmutable && $selectedDate->format("m") === $monthValue ? "true" : "false" ?>"
                ><?= e($monthName) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="month-picker-footer">
                <button type="button" class="month-picker-footer-button" data-month-picker-clear>Clear</button>
                <button type="button" class="month-picker-footer-button month-picker-current" data-month-picker-current>Current month</button>
            </div>
        </div>
    </div>
    <?php
};
?>
<section class="card" id="summary-section">
    <div class="summary-header">
        <div>
            <h2><?= e($company["system_name"]) ?> Summary</h2>
        </div>
        <div class="summary-actions">
            <a href="<?= e($printUrl) ?>" class="button-link secondary icon-button" target="_blank" rel="noopener" aria-label="Print filtered records" title="Print filtered records">
                <?= iconSvg("printer") ?>
                <span class="sr-only">Print filtered records</span>
            </a>
            <a href="<?= e($exportUrl) ?>" class="button-link secondary icon-button" aria-label="Export filtered Excel" title="Export filtered Excel">
                <?= iconSvg("download") ?>
                <span class="sr-only">Export filtered Excel</span>
            </a>
        </div>
    </div>

    <form action="index.php#summary-section" method="GET" class="summary-filter-form">
        <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
        <?php if (!empty($filters["data_correction_only"])): ?>
        <input type="hidden" name="data_correction" value="1">
        <?php endif; ?>
        <?php if (!empty($filters["escalation_only"])): ?>
        <input type="hidden" name="escalation" value="1">
        <?php endif; ?>

        <div class="summary-filter-grid">
            <?php $renderMonthPicker("filter-month-from", "month_from", "Month From", (string) ($filters["month_from"] ?? "")); ?>
            <?php $renderMonthPicker("filter-month-to", "month_to", "Month To", (string) ($filters["month_to"] ?? "")); ?>
            <div class="field">
                <label for="filter-day">Day</label>
                <input type="date" id="filter-day" name="day" value="<?= e($filters["day"] ?? "") ?>">
            </div>

            <?php if ($showBranchSelector): ?>
            <div class="field">
                <label for="filter-branch">Branch</label>
                <select id="filter-branch" name="branch">
                    <option value="">All branches</option>
                    <?php foreach ($branchOptions as $option): ?>
                    <option value="<?= e($option) ?>"<?= $filters["branch"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="field">
                <label for="filter-dealer">Dealers</label>
                <select id="filter-dealer" name="dealer">
                    <option value="">All dealers</option>
                    <?php foreach ($dealerOptions as $option): ?>
                    <option value="<?= e($option) ?>"<?= ($filters["dealer"] ?? "") === $option ? " selected" : "" ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="filter-identification-number">ID number</label>
                <input
                    type="text"
                    id="filter-identification-number"
                    name="id_number"
                    value="<?= e($filters["identification_number"] ?? "") ?>"
                    placeholder=""
                >
            </div>

            <div class="field">
                <label for="filter-user-name">User</label>
                <input
                    type="text"
                    id="filter-user-name"
                    name="user"
                    value="<?= e($filters["user_name"] ?? "") ?>"
                    <?= $userNameSuggestions !== [] ? 'list="filter-user-name-suggestions"' : "" ?>
                    placeholder=""
                >
                <?php if ($userNameSuggestions !== []): ?>
                <datalist id="filter-user-name-suggestions">
                    <?php foreach ($userNameSuggestions as $userNameSuggestion): ?>
                    <option value="<?= e(uppercaseText((string) $userNameSuggestion)) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="filter-status">Status</label>
                <select id="filter-status" name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($summaryStatusOptions as $option): ?>
                    <option value="<?= e($option) ?>"<?= $filters["status"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="filter-action">Action</label>
                <select id="filter-action" name="action">
                    <option value="">All actions</option>
                    <?php foreach ($monitoringActionOptions as $option): ?>
                    <option value="<?= e($option) ?>"<?= ($filters["disciplinary_action"] ?? "") === $option ? " selected" : "" ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="summary-toolbar">
            <div class="summary-actions">
                <button type="submit" class="primary icon-button" aria-label="Apply filters" title="Apply filters">
                    <?= iconSvg("search") ?>
                    <span class="sr-only">Apply filters</span>
                </button>
                <a href="<?= e($clearFiltersUrl . $summaryAnchor) ?>" class="button-link secondary icon-button" aria-label="Clear filters" title="Clear filters">
                    <?= iconSvg("x") ?>
                    <span class="sr-only">Clear filters</span>
                </a>
            </div>

            <div class="results-meta">
                <strong><?= e($pagination["start_item"]) ?>-<?= e($pagination["end_item"]) ?></strong> of <strong><?= e($totalRecords) ?></strong> records
            </div>
        </div>
        <br>
    </form>

    <?php if ($activeFilterBadges !== []): ?>
    <div class="active-filters" aria-label="Active filters">
        <?php foreach ($activeFilterBadges as $badge): ?>
        <span class="filter-badge"><?= e($badge) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($records === []): ?>
    <div class="summary-card-empty">No records matched the current filters.</div>
    <?php else: ?>
    <div class="summary-card-list">
        <?php foreach ($records as $row): ?>
            <?php
            $formattedDisciplinaryAction = trim((string) formatSummaryValue(
                ["key" => "disciplinary_action", "format" => "text"],
                $row
            ));
            $identificationNumber = trim((string) ($row["identification_number"] ?? ""));
            $hasFinalMemo = isFinalMemoMonitoringRecord($row);
            $recordUrl = $identificationNumber !== ""
                ? buildUrl("monitoring_record.php", $listQueryParams, ["identification_number" => $identificationNumber])
                : "";
            $editRecordUrl = $identificationNumber !== ""
                ? buildUrl("monitoring_record.php", $listQueryParams, [
                    "identification_number" => $identificationNumber,
                    "edit" => 1,
                ])
                : "";
            $titleValue = trim((string) formatSummaryValue(["key" => "user_name", "format" => "text"], $row));
            if ($titleValue === "") {
                $titleValue = trim((string) formatSummaryValue(["key" => "client_name", "format" => "text"], $row));
            }
            if ($titleValue === "") {
                $titleValue = $identificationNumber !== "" ? $identificationNumber : "UNASSIGNED";
            }
            $metaParts = array_filter([
                formatDisplayDate((string) ($row["date_recorded"] ?? "")),
                formatDisplayDate((string) ($row["transaction_date"] ?? "")),
                trim((string) formatSummaryValue(["key" => "module", "format" => "text"], $row)),
                trim((string) formatSummaryValue(["key" => "dealer", "format" => "text"], $row)),
                trim((string) formatSummaryValue(["key" => "branch", "format" => "text"], $row)),
            ]);
            $statusTags = splitMultiValueText((string) ($row["status"] ?? ""));
            $processedTypeTags = splitMultiValueText(formatMonitoringProcessedTypeDisplayValue($row));
            $classificationValue = trim((string) formatSummaryValue(["key" => "classification", "format" => "text"], $row));
            $ticketValue = trim((string) formatSummaryValue(["key" => "ticket", "format" => "text"], $row));
            $offenseValue = $formattedDisciplinaryAction !== ""
                ? $formattedDisciplinaryAction
                : trim((string) formatSummaryValue(["key" => "offense", "format" => "text"], $row));
            $memoStatusDisplayValue = formatMonitoringMemoActionStatusDisplayValue($row);
            $needsRefresherCourse = needsMonitoringRefresherCourse($row);
            $hasCompletedRefresherCourse = hasCompletedMonitoringRefresherCourse($row);
            $showRefresherCourseDoneButton = $needsRefresherCourse
                && isMonitoringRefresherCourseRecord($row);
            $alertActionDisplayValue = $needsRefresherCourse
                ? "USER NEEDS REFRESHER COURSE"
                : ($memoStatusDisplayValue !== "" ? $memoStatusDisplayValue : $offenseValue);
            $rowActionOptions = [];
            $hasIssuedMemo = getIssuedMonitoringMemoAction($row) !== "";
            $showIncidentReportResolveButton = !$hasFinalMemo && hasPendingMonitoringIncidentReportStatus($row);

            if (!$hasFinalMemo && !$hasIssuedMemo) {
                if (
                    !$showIncidentReportResolveButton
                    && !$needsRefresherCourse
                    && canMarkMonitoringRecordDone($row["status"] ?? "")
                ) {
                    $rowActionOptions[] = $monitoringDoneStatus;
                }

                if (
                    ((int) ($row["data_correction_offense_count"] ?? 0)) >= 2
                    && trim((string) ($row["disciplinary_action"] ?? "")) === ""
                ) {
                    foreach (getAvailableMonitoringMemoActionOptions($row) as $option) {
                        if (!in_array($option, $rowActionOptions, true)) {
                            $rowActionOptions[] = $option;
                        }
                    }
                }
            }
            ?>
        <article class="summary-card summary-card-monitoring<?= ((int) ($row["data_correction_offense_count"] ?? 0)) >= 1 ? " summary-card-alert" : "" ?>">
            <div class="summary-card-header">
                <div class="summary-card-main">
                    <?php if ($recordUrl !== ""): ?>
                    <a href="<?= e($recordUrl) ?>" class="dashboard-activity-id"><?= e($identificationNumber !== "" ? $identificationNumber : "NO ID") ?></a>
                    <?php else: ?>
                    <span class="dashboard-activity-id"><?= e($identificationNumber !== "" ? $identificationNumber : "NO ID") ?></span>
                    <?php endif; ?>

                    <div class="dashboard-activity-title"><?= e($titleValue) ?></div>
                    <?php if ($metaParts !== []): ?>
                    <div class="dashboard-activity-meta"><?= e(implode(" / ", $metaParts)) ?></div>
                    <?php endif; ?>
                </div>

                <div class="summary-card-action">
                    <?php if ($editRecordUrl !== ""): ?>
                    <a href="<?= e($editRecordUrl) ?>" class="button-link secondary icon-button summary-card-edit-link" aria-label="Edit record" title="Edit record">
                        <?= iconSvg("edit") ?>
                        <span>Edit</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($showIncidentReportResolveButton): ?>
                    <form action="update_monitoring_action.php" method="POST" class="monitoring-action-form">
                        <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
                        <input type="hidden" name="record_id" value="<?= e($row["id"] ?? "") ?>">
                        <input type="hidden" name="disciplinary_action" value="<?= e($monitoringIncidentReportResolvedAction) ?>">
                        <input type="hidden" name="filter_month_from" value="<?= e($filters["month_from"] ?? "") ?>">
                        <input type="hidden" name="filter_month_to" value="<?= e($filters["month_to"] ?? "") ?>">
                        <input type="hidden" name="filter_day" value="<?= e($filters["day"] ?? "") ?>">
                        <input type="hidden" name="filter_branch" value="<?= e($filters["branch"] ?? "") ?>">
                        <input type="hidden" name="filter_dealer" value="<?= e($filters["dealer"] ?? "") ?>">
                        <input type="hidden" name="filter_identification_number" value="<?= e($filters["identification_number"] ?? "") ?>">
                        <input type="hidden" name="filter_user_name" value="<?= e($filters["user_name"] ?? "") ?>">
                        <input type="hidden" name="filter_status" value="<?= e($filters["status"] ?? "") ?>">
                        <input type="hidden" name="filter_action" value="<?= e($filters["disciplinary_action"] ?? "") ?>">
                        <input type="hidden" name="filter_data_correction" value="<?= !empty($filters["data_correction_only"]) ? "1" : "" ?>">
                        <input type="hidden" name="filter_escalation" value="<?= !empty($filters["escalation_only"]) ? "1" : "" ?>">
                        <input type="hidden" name="filter_page" value="<?= e($pagination["page"]) ?>">
                        <button type="submit" class="secondary icon-button summary-card-edit-link" aria-label="Resolve incident report" title="Resolve incident report">
                            <?= iconSvg("check") ?>
                            <span>Resolve</span>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($showRefresherCourseDoneButton): ?>
                    <form
                        action="update_monitoring_action.php"
                        method="POST"
                        class="monitoring-action-form"
                        data-refresher-done-confirm
                    >
                        <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
                        <input type="hidden" name="record_id" value="<?= e($row["id"] ?? "") ?>">
                        <input type="hidden" name="mark_refresher_course_done" value="1">
                        <input type="hidden" name="filter_month_from" value="<?= e($filters["month_from"] ?? "") ?>">
                        <input type="hidden" name="filter_month_to" value="<?= e($filters["month_to"] ?? "") ?>">
                        <input type="hidden" name="filter_day" value="<?= e($filters["day"] ?? "") ?>">
                        <input type="hidden" name="filter_branch" value="<?= e($filters["branch"] ?? "") ?>">
                        <input type="hidden" name="filter_dealer" value="<?= e($filters["dealer"] ?? "") ?>">
                        <input type="hidden" name="filter_identification_number" value="<?= e($filters["identification_number"] ?? "") ?>">
                        <input type="hidden" name="filter_user_name" value="<?= e($filters["user_name"] ?? "") ?>">
                        <input type="hidden" name="filter_status" value="<?= e($filters["status"] ?? "") ?>">
                        <input type="hidden" name="filter_action" value="<?= e($filters["disciplinary_action"] ?? "") ?>">
                        <input type="hidden" name="filter_data_correction" value="<?= !empty($filters["data_correction_only"]) ? "1" : "" ?>">
                        <input type="hidden" name="filter_escalation" value="<?= !empty($filters["escalation_only"]) ? "1" : "" ?>">
                        <input type="hidden" name="filter_page" value="<?= e($pagination["page"]) ?>">
                        <button type="submit" class="secondary icon-button summary-card-edit-link" aria-label="Mark refresher course done" title="Mark refresher course done">
                            <?= iconSvg("check") ?>
                            <span>Done</span>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($rowActionOptions !== []): ?>
                    <form action="update_monitoring_action.php" method="POST" class="monitoring-action-form">
                        <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
                        <input type="hidden" name="record_id" value="<?= e($row["id"] ?? "") ?>">
                        <input type="hidden" name="filter_month_from" value="<?= e($filters["month_from"] ?? "") ?>">
                        <input type="hidden" name="filter_month_to" value="<?= e($filters["month_to"] ?? "") ?>">
                        <input type="hidden" name="filter_day" value="<?= e($filters["day"] ?? "") ?>">
                        <input type="hidden" name="filter_branch" value="<?= e($filters["branch"] ?? "") ?>">
                        <input type="hidden" name="filter_dealer" value="<?= e($filters["dealer"] ?? "") ?>">
                        <input type="hidden" name="filter_identification_number" value="<?= e($filters["identification_number"] ?? "") ?>">
                        <input type="hidden" name="filter_user_name" value="<?= e($filters["user_name"] ?? "") ?>">
                        <input type="hidden" name="filter_status" value="<?= e($filters["status"] ?? "") ?>">
                        <input type="hidden" name="filter_action" value="<?= e($filters["disciplinary_action"] ?? "") ?>">
                        <input type="hidden" name="filter_data_correction" value="<?= !empty($filters["data_correction_only"]) ? "1" : "" ?>">
                        <input type="hidden" name="filter_escalation" value="<?= !empty($filters["escalation_only"]) ? "1" : "" ?>">
                        <input type="hidden" name="filter_page" value="<?= e($pagination["page"]) ?>">
                        <select
                            name="disciplinary_action"
                            class="summary-action-select"
                            aria-label="Choose disciplinary action"
                            title="Choose Action"
                            onchange="if (this.value !== '') { this.form.submit(); }"
                        >
                            <option value="" selected disabled>Choose action</option>
                            <?php foreach ($rowActionOptions as $option): ?>
                            <option value="<?= e($option) ?>"><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="summary-card-tags">
                <?php foreach ($statusTags as $statusTag): ?>
                <span class="dashboard-chip"><?= e(uppercaseText($statusTag)) ?></span>
                <?php endforeach; ?>

                <?php foreach ($processedTypeTags as $processedTypeTag): ?>
                <span class="dashboard-chip"><?= e(uppercaseText($processedTypeTag)) ?></span>
                <?php endforeach; ?>

                <?php if ($classificationValue !== ""): ?>
                <span class="dashboard-chip"><?= e(uppercaseText($classificationValue)) ?></span>
                <?php endif; ?>

                <?php if (((int) ($row["data_correction_offense_count"] ?? 0)) > 0): ?>
                <span class="dashboard-chip alert"><?= e((string) ($row["data_correction_offense_count"] ?? 0)) ?> USER ERROR<?= ((int) ($row["data_correction_offense_count"] ?? 0)) > 1 ? "S" : "" ?></span>
                <?php endif; ?>

                <?php if ($hasFinalMemo): ?>
                <span class="dashboard-chip final-memo">Final memo issued</span>
                <?php endif; ?>

                <?php if ($needsRefresherCourse): ?>
                <span class="dashboard-chip alert">REFRESHER COURSE REQUIRED</span>
                <?php elseif ($hasCompletedRefresherCourse): ?>
                <span class="dashboard-chip">REFRESHER COURSE DONE</span>
                <?php endif; ?>

                <?php if ($ticketValue !== ""): ?>
                <span class="dashboard-chip ticket">TICKET <?= e(uppercaseText($ticketValue)) ?></span>
                <?php endif; ?>
            </div>

            <div class="summary-card-grid">
                <div class="summary-card-field summary-card-field-department">
                    <div class="summary-card-label">Department</div>
                    <div class="summary-card-value"><?= e($formatCardValue((string) formatSummaryValue(["key" => "department", "format" => "text"], $row))) ?></div>
                </div>
                <div class="summary-card-field summary-card-field-client">
                    <div class="summary-card-label">Client name</div>
                    <div class="summary-card-value"><?= e($formatCardValue((string) formatSummaryValue(["key" => "client_name", "format" => "text"], $row))) ?></div>
                </div>
                <div class="summary-card-field summary-card-field-reference">
                    <div class="summary-card-label">Transaction reference</div>
                    <div class="summary-card-value"><?= e($formatCardValue((string) formatSummaryValue(["key" => "invoice_reference", "format" => "text"], $row))) ?></div>
                </div>
                <div class="summary-card-field summary-card-field-approved">
                    <div class="summary-card-label">Approved by</div>
                    <div class="summary-card-value"><?= e($formatCardValue((string) formatSummaryValue(["key" => "approved_by", "format" => "text"], $row))) ?></div>
                </div>
                <div class="summary-card-field summary-card-field-processed">
                    <div class="summary-card-label">Processed by</div>
                    <div class="summary-card-value"><?= e($formatCardValue((string) formatSummaryValue(["key" => "processed_by", "format" => "text"], $row))) ?></div>
                </div>
                <div class="summary-card-field summary-card-field-alert">
                    <div class="summary-card-label">Alert / action</div>
                    <div class="summary-card-value"><?= e($formatCardValue($alertActionDisplayValue)) ?></div>
                </div>
                <div class="summary-card-field summary-card-field-reason">
                    <div class="summary-card-label">Reason</div>
                    <div class="summary-card-value summary-card-value-multiline"><?= e($formatCardValue((string) formatSummaryValue(["key" => "reason", "format" => "text"], $row))) ?></div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($pagination["total_pages"] > 1): ?>
    <nav class="pagination" aria-label="Summary pages">
        <?php if ($pagination["has_previous"]): ?>
        <a href="<?= e(buildUrl("index.php", $listQueryParams, ["page" => $pagination["page"] - 1]) . $summaryAnchor) ?>" class="button-link secondary icon-button" aria-label="Previous page" title="Previous page">
            <?= iconSvg("arrow-left") ?>
            <span class="sr-only">Previous page</span>
        </a>
        <?php else: ?>
        <span class="button-link secondary disabled icon-button" aria-disabled="true" aria-label="Previous page" title="Previous page">
            <?= iconSvg("arrow-left") ?>
            <span class="sr-only">Previous page</span>
        </span>
        <?php endif; ?>

        <div class="page-numbers">
            <?php foreach ($paginationPages as $pageNumber): ?>
            <a
                href="<?= e(buildUrl("index.php", $listQueryParams, ["page" => $pageNumber]) . $summaryAnchor) ?>"
                class="page-number<?= $pageNumber === $pagination["page"] ? " active" : "" ?>"
                <?= $pageNumber === $pagination["page"] ? 'aria-current="page"' : "" ?>
            ><?= e($pageNumber) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($pagination["has_next"]): ?>
        <a href="<?= e(buildUrl("index.php", $listQueryParams, ["page" => $pagination["page"] + 1]) . $summaryAnchor) ?>" class="button-link secondary icon-button" aria-label="Next page" title="Next page">
            <?= iconSvg("arrow-right") ?>
            <span class="sr-only">Next page</span>
        </a>
        <?php else: ?>
        <span class="button-link secondary disabled icon-button" aria-disabled="true" aria-label="Next page" title="Next page">
            <?= iconSvg("arrow-right") ?>
            <span class="sr-only">Next page</span>
        </span>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</section>
