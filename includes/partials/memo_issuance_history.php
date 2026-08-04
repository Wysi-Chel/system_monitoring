<section class="transaction-memo-history" id="memo-issuance-history">
    <div class="transaction-memo-history-header">
        <span class="user-transaction-history-kicker">Memo activity</span>
        <h3>Memo / Refresher Course History</h3>
    </div>

    <?php if ($recordUserName === ""): ?>
    <p class="note">Add a user name to view related memo and refresher course actions.</p>
    <?php elseif ($memoIssuanceRecords === []): ?>
    <div class="summary-card-empty">No memo or refresher course actions have been recorded for this user.</div>
    <?php else: ?>
    <div class="table-wrapper compact-summary-table-wrapper">
        <table class="compact-summary-table memo-issuance-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Action Date</th>
                    <th>Date Recorded</th>
                    <th>ID Number</th>
                    <th>Action</th>
                    <th>Printed</th>
                    <th>Print</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($memoIssuanceRecords as $memoRow): ?>
                    <?php
                    $memoRecordId = (int) ($memoRow["id"] ?? 0);
                    $memoIdentificationNumber = trim((string) ($memoRow["identification_number"] ?? ""));
                    $isRefresherCourse = isMonitoringRefresherCourseRecord($memoRow);
                    $memoAction = $isRefresherCourse
                        ? "Refresher Course"
                        : normalizeMonitoringMemoAction((string) ($memoRow["disciplinary_action"] ?? ""));
                    $memoIssued = hasConfirmedMonitoringMemoIssued($memoRow);
                    $memoIssuedAt = trim((string) ($memoRow["memo_issued_at"] ?? ""));
                    $memoPrintedAt = trim((string) ($memoRow["memo_printed_at"] ?? ""));
                    $refresherCompleted = hasCompletedMonitoringRefresherCourse($memoRow);
                    $refresherCompletedAt = trim((string) ($memoRow["refresher_completed_at"] ?? ""));
                    $memoIssueFormId = "memo-issued-form-" . $memoRecordId;
                    $refresherDoneFormId = "refresher-done-form-" . $memoRecordId;
                    $memoPrintUrl = buildUrl("export_memo_docx.php", [
                        "company" => $company["key"],
                        "identification_number" => $memoIdentificationNumber,
                        "reprint" => $memoPrintedAt !== "" ? 1 : null,
                    ]);
                    ?>
                <tr>
                    <td class="memo-check-cell">
                        <?php if ($isRefresherCourse && $refresherCompleted): ?>
                        <label class="memo-issued-check memo-issued-check-complete" title="Refresher course done">
                            <input type="checkbox" checked disabled aria-label="Refresher Course done">
                            <span><?= iconSvg("check") ?></span>
                        </label>
                        <?php elseif ($isRefresherCourse): ?>
                        <form
                            id="<?= e($refresherDoneFormId) ?>"
                            action="update_monitoring_action.php"
                            method="POST"
                            class="monitoring-action-form memo-issued-form"
                            data-refresher-done-confirm
                        >
                            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
                            <input type="hidden" name="record_id" value="<?= e($memoRecordId) ?>">
                            <input type="hidden" name="mark_refresher_course_done" value="1">
                            <input type="hidden" name="return_identification_number" value="<?= e($identificationNumber) ?>">
                            <button type="submit" class="secondary icon-button" aria-label="Mark refresher course done" title="Mark refresher course done">
                                <?= iconSvg("check") ?>
                                <span>Done</span>
                            </button>
                        </form>
                        <?php elseif ($memoIssued): ?>
                        <label class="memo-issued-check memo-issued-check-complete">
                            <input type="checkbox" checked disabled aria-label="<?= e($memoAction) ?> issued">
                            <span><?= iconSvg("check") ?></span>
                        </label>
                        <?php else: ?>
                        <form id="<?= e($memoIssueFormId) ?>" action="update_monitoring_action.php" method="POST" class="monitoring-action-form memo-issued-form" data-memo-issued-confirm>
                            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
                            <input type="hidden" name="record_id" value="<?= e($memoRecordId) ?>">
                            <input type="hidden" name="mark_memo_issued" value="1">
                            <input type="hidden" name="return_identification_number" value="<?= e($identificationNumber) ?>">
                            <label class="memo-issued-check">
                                <input
                                    type="checkbox"
                                    aria-label="Mark <?= e($memoAction) ?> as issued"
                                    onchange="if (this.checked) { this.form.requestSubmit(); }"
                                >
                                <span><?= iconSvg("check") ?></span>
                            </label>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isRefresherCourse && $refresherCompletedAt !== ""): ?>
                        <?= e(formatDisplayDate($refresherCompletedAt)) ?>
                        <?php elseif ($isRefresherCourse): ?>
                        Pending
                        <?php elseif ($memoIssuedAt !== ""): ?>
                        <?= e(formatDisplayDate($memoIssuedAt)) ?>
                        <?php else: ?>
                        <input
                            type="date"
                            name="memo_issued_date"
                            value="<?= e($today) ?>"
                            max="<?= e($today) ?>"
                            required
                            form="<?= e($memoIssueFormId) ?>"
                            aria-label="Memo issue date"
                            title="Memo issue date"
                        >
                        <?php endif; ?>
                    </td>
                    <td><?= e(formatMonitoringDetailDisplayValue(["key" => "date_recorded", "format" => "date"], $memoRow)) ?></td>
                    <td><?= e($memoIdentificationNumber !== "" ? $memoIdentificationNumber : "N/A") ?></td>
                    <td><?= e($memoAction) ?></td>
                    <td>
                        <?= e(
                            $isRefresherCourse
                                ? "N/A"
                                : ($memoPrintedAt !== "" ? formatDisplayTimestamp($memoPrintedAt) : "Not printed")
                        ) ?>
                    </td>
                    <td>
                        <?php if ($isRefresherCourse): ?>
                        <span class="note">N/A</span>
                        <?php else: ?>
                        <a
                            href="<?= e($memoPrintUrl) ?>"
                            class="button-link secondary icon-button memo-history-print"
                            data-memo-print-link
                            aria-label="<?= e($memoPrintedAt !== "" ? "Reprint " . $memoAction : "Print " . $memoAction) ?>"
                            title="<?= e($memoPrintedAt !== "" ? "Reprint memo" : "Print memo") ?>"
                        >
                            <?= iconSvg("printer") ?>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
