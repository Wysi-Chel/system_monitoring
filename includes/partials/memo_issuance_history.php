<section class="card" id="memo-issuance-history">
    <div class="summary-header">
        <div>
            <h2>Memo Issuance History</h2>
        </div>
    </div>

    <?php if ($recordUserName === ""): ?>
    <p class="note">Add a user name to view related memo issuances.</p>
    <?php elseif ($memoIssuanceRecords === []): ?>
    <div class="summary-card-empty">No memo actions have been recorded for this user.</div>
    <?php else: ?>
    <div class="table-wrapper compact-summary-table-wrapper">
        <table class="compact-summary-table memo-issuance-table">
            <thead>
                <tr>
                    <th>Issued</th>
                    <th>Date Issued</th>
                    <th>Date Recorded</th>
                    <th>ID Number</th>
                    <th>Memo</th>
                    <th>Printed</th>
                    <th>Print</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($memoIssuanceRecords as $memoRow): ?>
                    <?php
                    $memoRecordId = (int) ($memoRow["id"] ?? 0);
                    $memoIdentificationNumber = trim((string) ($memoRow["identification_number"] ?? ""));
                    $memoAction = normalizeMonitoringMemoAction((string) ($memoRow["disciplinary_action"] ?? ""));
                    $memoIssued = hasConfirmedMonitoringMemoIssued($memoRow);
                    $memoIssuedAt = trim((string) ($memoRow["memo_issued_at"] ?? ""));
                    $memoPrintedAt = trim((string) ($memoRow["memo_printed_at"] ?? ""));
                    $memoIssueFormId = "memo-issued-form-" . $memoRecordId;
                    $memoPrintUrl = buildUrl("export_memo_docx.php", [
                        "company" => $company["key"],
                        "identification_number" => $memoIdentificationNumber,
                        "reprint" => $memoPrintedAt !== "" ? 1 : null,
                    ]);
                    ?>
                <tr>
                    <td class="memo-check-cell">
                        <?php if ($memoIssued): ?>
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
                        <?php if ($memoIssuedAt !== ""): ?>
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
                    <td><?= e($memoPrintedAt !== "" ? formatDisplayTimestamp($memoPrintedAt) : "Not printed") ?></td>
                    <td>
                        <a
                            href="<?= e($memoPrintUrl) ?>"
                            class="button-link secondary icon-button memo-history-print"
                            data-memo-print-link
                            aria-label="<?= e($memoPrintedAt !== "" ? "Reprint " . $memoAction : "Print " . $memoAction) ?>"
                            title="<?= e($memoPrintedAt !== "" ? "Reprint memo" : "Print memo") ?>"
                        >
                            <?= iconSvg("printer") ?>
                            <span class="sr-only"><?= e($memoPrintedAt !== "" ? "Reprint memo" : "Print memo") ?></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
