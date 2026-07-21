<?php
require __DIR__ . "/config.php";
require __DIR__ . "/includes/monitoring_options.php";
require __DIR__ . "/includes/monitoring_helpers.php";
require __DIR__ . "/includes/monitoring_repository.php";

$pendingRow = [
    "memo_printed_at" => null,
    "disciplinary_action" => "Written Memo",
    "action_taken" => "Written Memo",
    "offense" => "Written Memo",
];
$printedRow = $pendingRow;
$printedRow["memo_printed_at"] = "2026-07-17 13:41:59";

echo formatMonitoringMemoActionStatusDisplayValue($pendingRow) . "\n";
echo formatMonitoringMemoActionStatusDisplayValue($printedRow) . "\n";

$company = resolveCompanyConfig("mitsubishi", $companyConfigs);
ensureMonitoringTable($pdo, $company);
$tableNameSql = quoteMysqlIdentifier($company["table_name"]);
$row = fetchMonitoringRecordByIdentificationNumber($pdo, $tableNameSql, "000012");
if ($row === null) {
    throw new RuntimeException("Smoke-test record is missing.");
}

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE {$tableNameSql} SET memo_printed_at = NULL WHERE id = :id")
        ->execute([":id" => (int) $row["id"]]);
    $firstClaim = markMonitoringMemoPrinted($pdo, $tableNameSql, (int) $row["id"], "Verbal Memo");
    $secondClaim = markMonitoringMemoPrinted($pdo, $tableNameSql, (int) $row["id"], "Verbal Memo");
    echo $firstClaim ? "FIRST_CLAIM_OK\n" : "FIRST_CLAIM_FAILED\n";
    echo !$secondClaim ? "SECOND_CLAIM_BLOCKED\n" : "SECOND_CLAIM_ALLOWED\n";
} finally {
    $pdo->rollBack();
}
