<?php
require __DIR__ . "/config.php";
require __DIR__ . "/includes/monitoring_options.php";
require __DIR__ . "/includes/monitoring_helpers.php";
require __DIR__ . "/includes/monitoring_repository.php";

$pendingRow = [
    "memo_printed_at" => null,
    "memo_issued_at" => null,
    "disciplinary_action" => "Written Memo",
    "action_taken" => "Written Memo",
    "offense" => "Written Memo",
];
$printedRow = $pendingRow;
$printedRow["memo_printed_at"] = "2026-07-17 13:41:59";
$issuedRow = $printedRow;
$issuedRow["memo_issued_at"] = "2026-07-17 14:05:00";

echo formatMonitoringMemoActionStatusDisplayValue($pendingRow) . "\n";
echo formatMonitoringMemoActionStatusDisplayValue($printedRow) . "\n";
echo formatMonitoringMemoActionStatusDisplayValue($issuedRow) . "\n";

$company = resolveCompanyConfig("mitsubishi", $companyConfigs);
ensureMonitoringTable($pdo, $company);
$tableNameSql = quoteMysqlIdentifier($company["table_name"]);
$row = fetchMonitoringRecordByIdentificationNumber($pdo, $tableNameSql, "000012");
if ($row === null) {
    throw new RuntimeException("Smoke-test record is missing.");
}

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE {$tableNameSql} SET memo_printed_at = NULL, memo_issued_at = NULL WHERE id = :id")
        ->execute([":id" => (int) $row["id"]]);
    markMonitoringMemoPrinted($pdo, $tableNameSql, (int) $row["id"], "Verbal Memo");
    $printedRecord = fetchMonitoringRecordById($pdo, $tableNameSql, (int) $row["id"]);
    echo hasPrintedMonitoringMemo($printedRecord ?? []) ? "PRINT_TRACKED\n" : "PRINT_NOT_TRACKED\n";
    echo getIssuedMonitoringMemoAction($printedRecord ?? []) === "" ? "NOT_AUTO_ISSUED\n" : "AUTO_ISSUED\n";

    $firstConfirmation = markMonitoringMemoIssued($pdo, $tableNameSql, (int) $row["id"]);
    $secondConfirmation = markMonitoringMemoIssued($pdo, $tableNameSql, (int) $row["id"]);
    $issuedRecord = fetchMonitoringRecordById($pdo, $tableNameSql, (int) $row["id"]);
    echo $firstConfirmation ? "ISSUE_CONFIRMED\n" : "ISSUE_CONFIRMATION_FAILED\n";
    echo !$secondConfirmation ? "SECOND_CONFIRMATION_IGNORED\n" : "SECOND_CONFIRMATION_CHANGED\n";
    echo getIssuedMonitoringMemoAction($issuedRecord ?? []) === "Verbal Memo" ? "ISSUED_STATUS_OK\n" : "ISSUED_STATUS_FAILED\n";
    echo strpos(formatMonitoringMemoActionStatusDisplayValue($issuedRecord ?? []), "(") !== false ? "ISSUED_DATE_OK\n" : "ISSUED_DATE_FAILED\n";
} finally {
    $pdo->rollBack();
}
