<?php
require __DIR__ . "/includes/auth.php";
requireMonitoringAuthentication();
require "config.php";
require __DIR__ . "/includes/monitoring_options.php";
require __DIR__ . "/includes/monitoring_helpers.php";
require __DIR__ . "/includes/monitoring_repository.php";

$company = resolveCompanyConfig($_POST["company"] ?? $_GET["company"] ?? null, $companyConfigs);
ensureMonitoringTable($pdo, $company);
$tableNameSql = quoteMysqlIdentifier($company["table_name"]);

$recordId = is_numeric($_POST["record_id"] ?? null) ? (int) $_POST["record_id"] : 0;
$disciplinaryAction = trim((string) ($_POST["disciplinary_action"] ?? $_POST["action_taken"] ?? ""));
$markMemoIssued = ($_POST["mark_memo_issued"] ?? "") === "1";
$memoIssuedDate = trim((string) ($_POST["memo_issued_date"] ?? ""));
$returnIdentificationNumber = normalizeIdentificationNumberFilter(
    $_POST["return_identification_number"] ?? ""
);
$allowedActions = getMonitoringActionOptions();
$doneStatus = getMonitoringDoneStatus();
$incidentReportResolvedAction = getMonitoringIncidentReportResolvedAction();

$redirectParams = [
    "company" => $company["key"],
];

$legacyFilterMonth = trim((string) ($_POST["filter_month"] ?? ""));
$filterMonthFrom = trim((string) ($_POST["filter_month_from"] ?? $legacyFilterMonth));
$filterMonthTo = trim((string) ($_POST["filter_month_to"] ?? $legacyFilterMonth));
$filterDay = trim((string) ($_POST["filter_day"] ?? ""));
$filterBranch = trim((string) ($_POST["filter_branch"] ?? ""));
$filterDealer = trim((string) ($_POST["filter_dealer"] ?? ""));
$filterIdentificationNumber = trim((string) ($_POST["filter_identification_number"] ?? ""));
$filterUserName = trim((string) ($_POST["filter_user_name"] ?? ""));
$filterStatus = trim((string) ($_POST["filter_status"] ?? ""));
$filterAction = trim((string) ($_POST["filter_action"] ?? ""));
$filterDataCorrection = trim((string) ($_POST["filter_data_correction"] ?? ""));
$filterEscalation = trim((string) ($_POST["filter_escalation"] ?? ""));
$filterPage = trim((string) ($_POST["filter_page"] ?? ""));

if ($filterMonthFrom !== "") {
    $redirectParams["month_from"] = $filterMonthFrom;
}
if ($filterMonthTo !== "") {
    $redirectParams["month_to"] = $filterMonthTo;
}
if ($filterDay !== "") {
    $redirectParams["day"] = $filterDay;
}

if ($filterBranch !== "") {
    $redirectParams["branch"] = $filterBranch;
}

if ($filterDealer !== "") {
    $redirectParams["dealer"] = $filterDealer;
}

if ($filterIdentificationNumber !== "") {
    $redirectParams["id_number"] = $filterIdentificationNumber;
}

if ($filterUserName !== "") {
    $redirectParams["user"] = $filterUserName;
}

if ($filterStatus !== "") {
    $redirectParams["status"] = $filterStatus;
}

if ($filterAction !== "") {
    $redirectParams["action"] = $filterAction;
}

if ($filterDataCorrection === "1") {
    $redirectParams["data_correction"] = 1;
}

if ($filterEscalation === "1") {
    $redirectParams["escalation"] = 1;
}

if ($filterPage !== "" && $filterPage !== "1") {
    $redirectParams["page"] = $filterPage;
}

$redirectLocation = $returnIdentificationNumber !== ""
    ? "monitoring_record.php?" . http_build_query([
        "company" => $company["key"],
        "identification_number" => $returnIdentificationNumber,
    ]) . "#memo-issuance-history"
    : "index.php?" . http_build_query($redirectParams) . "#summary-section";

if ($recordId > 0 && ($disciplinaryAction !== "" || $markMemoIssued)) {
    $record = fetchMonitoringRecordById($pdo, $tableNameSql, $recordId);

    if ($record !== null) {
        $recordMemoAction = normalizeMonitoringMemoAction((string) ($record["disciplinary_action"] ?? ""));

        if (
            $markMemoIssued
            && isUserErrorMonitoringRecord($record)
            && in_array($recordMemoAction, ["Verbal Memo", "Written Memo", "Final Memo"], true)
            && getIssuedMonitoringMemoAction($record) === ""
        ) {
            markMonitoringMemoIssued($pdo, $tableNameSql, $recordId, $memoIssuedDate);
            header("Location: " . $redirectLocation);
            exit;
        }

        if (isFinalMemoMonitoringRecord($record)) {
            header("Location: " . $redirectLocation);
            exit;
        }

        if ($disciplinaryAction === $incidentReportResolvedAction && hasPendingMonitoringIncidentReportStatus($record)) {
            updateMonitoringRecordStatus(
                $pdo,
                $tableNameSql,
                $recordId,
                resolveMonitoringIncidentReportStatus($record["status"] ?? "")
            );
        } elseif ($disciplinaryAction === $doneStatus && canMarkMonitoringRecordDone($record["status"] ?? "")) {
            updateMonitoringRecordStatus($pdo, $tableNameSql, $recordId, $doneStatus);
        } elseif (in_array($disciplinaryAction, $allowedActions, true)) {
            $enrichedRecord = enrichMonitoringRecordsWithDataCorrectionActions($pdo, $tableNameSql, [$record])[0] ?? null;

            if (
                $enrichedRecord !== null
                && uppercaseText(trim((string) ($enrichedRecord["classification"] ?? ""))) === uppercaseText("User Error")
                && (int) ($enrichedRecord["data_correction_offense_count"] ?? 0) >= 1
                && in_array($disciplinaryAction, getAvailableMonitoringMemoActionOptions($enrichedRecord), true)
            ) {
                updateMonitoringRecordActionTaken($pdo, $tableNameSql, $recordId, $disciplinaryAction);

                if (
                    uppercaseText($disciplinaryAction) === uppercaseText(getMonitoringIncidentReportOffense())
                    && !containsMultiValueText((string) ($record["status"] ?? ""), "Pending")
                ) {
                    $statusValues = splitMultiValueText((string) ($record["status"] ?? ""));
                    $statusValues[] = "Pending";
                    updateMonitoringRecordStatus(
                        $pdo,
                        $tableNameSql,
                        $recordId,
                        implode(", ", array_values(array_unique($statusValues)))
                    );
                }
            }
        }
    }
}

header("Location: " . $redirectLocation);
exit;
?>
