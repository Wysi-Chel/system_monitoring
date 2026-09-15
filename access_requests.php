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
$userAccessTableNameSql = quoteMysqlIdentifier($company["user_access_table_name"]);
$isAccessView = ($_GET["view"] ?? "") === "accesses";
$viewParams = $isAccessView ? ["view" => "accesses"] : [];
$companyDealerOptions = $company["access_request_dealers"] ?? [];
$filterOptions = [
    "dealer" => $companyDealerOptions,
    "status" => $isAccessView ? [] : $accessRequestStatusOptions,
    "per_page" => $rowsPerPageOptions,
];

$filters = buildAccessRequestFilters($_GET, $filterOptions);
$totalRecords = $isAccessView
    ? countUserAccessUsers($pdo, $userAccessTableNameSql, $filters)
    : countAccessRequests($pdo, $accessRequestTableNameSql, $filters);
$pagination = buildPaginationState($filters["page"], $filters["per_page"], $totalRecords);
$filters["page"] = $pagination["page"];
$records = $isAccessView ? [] : fetchAccessRequests(
    $pdo,
    $accessRequestTableNameSql,
    $filters,
    $accessRequestStatusOptions,
    $pagination["limit"],
    $pagination["offset"]
);
$userAccessGroups = $isAccessView
    ? fetchUserAccessGroups($pdo, $userAccessTableNameSql, $filters, $pagination["limit"], $pagination["offset"])
    : [];
$statusCounts = countAccessRequestsByStatus($pdo, $accessRequestTableNameSql, $accessRequestStatusOptions);
$accessRequestQueryParams = buildMonitoringListQueryParams($company["key"], $filters) + $viewParams;
$mitsubishiUrl = buildUrl("access_requests.php", $accessRequestQueryParams, [
    "company" => "mitsubishi",
    "dealer" => null,
    "page" => 1,
]);
$hyundaiUrl = buildUrl("access_requests.php", $accessRequestQueryParams, [
    "company" => "hyundai",
    "dealer" => null,
    "page" => 1,
]);
$clearFiltersUrl = buildUrl("access_requests.php", ["company" => $company["key"]] + $viewParams);
$accessRequestSummaryAnchor = "#access-request-summary";
$requestsViewUrl = buildUrl("access_requests.php", ["company" => $company["key"]]) . $accessRequestSummaryAnchor;
$accessesViewUrl = buildUrl("access_requests.php", ["company" => $company["key"], "view" => "accesses"]) . $accessRequestSummaryAnchor;
$activeFilterBadges = buildAccessRequestFilterBadges($filters);
$paginationPages = buildPaginationPages($pagination["page"], $pagination["total_pages"]);
$headerKicker = $company["company_name"];
$headerTitle = "Access Requests";
$showCompanySwitch = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($company["company_name"]) ?> Access Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="<?= e($company["logo_type"]) ?>" href="<?= e($company["logo_path"]) ?>">
    <link rel="shortcut icon" type="<?= e($company["logo_type"]) ?>" href="<?= e($company["logo_path"]) ?>">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= e(buildVersionedAssetPath("assets/css/index.css")) ?>">
</head>
<body class="company-<?= e($company["key"]) ?> page-ticket-monitoring page-access-requests">
<?php require __DIR__ . "/includes/partials/page_header.php"; ?>

<main>
    <section class="card access-request-status-card" aria-label="Access requests by status">
        <div class="access-request-status-grid">
            <?php foreach ($accessRequestStatusOptions as $statusOption): ?>
            <?php $isActiveStatus = !$isAccessView && $filters["status"] === $statusOption; ?>
            <a
                href="<?= e(buildUrl("access_requests.php", ["company" => $company["key"], "status" => $statusOption]) . $accessRequestSummaryAnchor) ?>"
                class="summary-card-field access-request-status-count<?= $isActiveStatus ? " active" : "" ?>"
                <?= $isActiveStatus ? 'aria-current="true"' : "" ?>
            >
                <span class="summary-card-label"><?= e($statusOption) ?></span>
                <span class="summary-card-value"><?= e($statusCounts[$statusOption] ?? 0) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card" id="access-request-summary">
        <div class="summary-header">
            <div>
                <h2><?= $isAccessView ? "Recorded User Accesses" : "DMIS Access Requests" ?></h2>
            </div>
            <a href="public_access_request.php" class="button-link secondary icon-button" target="_blank" rel="noopener" aria-label="Open public access request form" title="Open public access request form">
                <?= iconSvg("external-link") ?>
                <span class="sr-only">Open public access request form</span>
            </a>
        </div>

        <nav class="access-request-tabs" aria-label="Access request views">
            <a href="<?= e($requestsViewUrl) ?>" class="access-request-tab<?= !$isAccessView ? " active" : "" ?>"<?= !$isAccessView ? ' aria-current="page"' : "" ?>>
                <?= iconSvg("lock") ?>
                <span>Requests</span>
            </a>
            <a href="<?= e($accessesViewUrl) ?>" class="access-request-tab<?= $isAccessView ? " active" : "" ?>"<?= $isAccessView ? ' aria-current="page"' : "" ?>>
                <?= iconSvg("users") ?>
                <span>User Accesses</span>
            </a>
        </nav>

        <form action="access_requests.php#access-request-summary" method="GET" class="summary-filter-form">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">
            <?php if ($isAccessView): ?>
            <input type="hidden" name="view" value="accesses">
            <?php endif; ?>

            <div class="summary-filter-grid">
                <div class="field">
                    <label for="access-request-search"><?= $isAccessView ? "Access search" : "Request search" ?></label>
                    <input type="search" id="access-request-search" name="q" value="<?= e($filters["search"]) ?>" placeholder="<?= $isAccessView ? "Username, name, module, access, or reference" : "Reference, name, username, module, or department" ?>">
                </div>

                <div class="field">
                    <label for="access-request-dealer">Dealers</label>
                    <select id="access-request-dealer" name="dealer">
                        <option value="">All dealers</option>
                        <?php foreach ($companyDealerOptions as $option): ?>
                        <option value="<?= e($option) ?>"<?= $filters["dealer"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!$isAccessView): ?>
                <div class="field">
                    <label for="access-request-status">Status</label>
                    <select id="access-request-status" name="status">
                        <option value="">All statuses</option>
                        <?php foreach ($accessRequestStatusOptions as $option): ?>
                        <option value="<?= e($option) ?>"<?= $filters["status"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="summary-toolbar">
                <div class="summary-actions">
                    <button type="submit" class="primary icon-button" aria-label="Apply filters" title="Apply filters">
                        <?= iconSvg("search") ?>
                        <span class="sr-only">Apply filters</span>
                    </button>
                    <a href="<?= e($clearFiltersUrl . $accessRequestSummaryAnchor) ?>" class="button-link secondary icon-button" aria-label="Clear filters" title="Clear filters">
                        <?= iconSvg("x") ?>
                        <span class="sr-only">Clear filters</span>
                    </a>
                </div>
<br><br><br>
                <div class="results-meta">
                    <strong><?= e($pagination["start_item"]) ?>-<?= e($pagination["end_item"]) ?></strong> of <strong><?= e($totalRecords) ?></strong> <?= $isAccessView ? "users" : "access requests" ?>
                </div>
            </div>
        </form>

        <?php if ($activeFilterBadges !== []): ?>
        <div class="active-filters" aria-label="Active filters">
            <?php foreach ($activeFilterBadges as $badge): ?>
            <span class="filter-badge"><?= e($badge) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($isAccessView): ?>
            <?php if ($userAccessGroups === []): ?>
            <div class="summary-card-empty">No recorded user accesses matched the current filters. Accesses are recorded when IT marks an approved request as implemented.</div>
            <?php else: ?>
            <div class="summary-card-list">
                <?php foreach ($userAccessGroups as $group): ?>
                    <?php
                    $latestAccess = array_reduce(
                        $group["accesses"],
                        static fn(?array $latest, array $access): array => $latest === null || (string) $access["granted_at"] > (string) $latest["granted_at"] ? $access : $latest
                    );
                    $metaParts = array_filter([
                        trim((string) ($latestAccess["dealer"] ?? "")),
                        trim((string) ($latestAccess["department"] ?? "")),
                    ]);
                    $accessCount = count($group["accesses"]);
                    ?>
                <article class="summary-card">
                    <div class="summary-card-header">
                        <div class="summary-card-main">
                            <span class="dashboard-activity-id"><?= e($group["dmis_username"]) ?></span>
                            <div class="dashboard-activity-title"><?= e($latestAccess["requester_name"] ?? "") ?></div>
                            <?php if ($metaParts !== []): ?>
                            <div class="dashboard-activity-meta"><?= e(implode(" / ", $metaParts)) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="summary-card-action">
                            <span class="status-pill status-pill-implemented"><?= e($accessCount) ?> <?= $accessCount === 1 ? "module" : "modules" ?></span>
                        </div>
                    </div>

                    <div class="summary-card-grid">
                        <?php foreach ($group["accesses"] as $access): ?>
                        <?php $accessDetails = mb_strtoupper(trim((string) ($access["access_details"] ?? "")), 'UTF-8'); ?>
                        <div class="summary-card-field">
                            <div class="summary-card-label"><?= e($access["module"]) ?></div>
                            <div class="summary-card-value"><?= e($accessDetails !== "" ? $accessDetails : "Access granted") ?></div>
                            <a href="<?= e(buildUrl("access_request_view.php", ["company" => $company["key"], "id" => (int) $access["access_request_id"]])) ?>" class="access-list-source" title="Approved by <?= e($access["approved_by"]) ?>, implemented by <?= e($access["implemented_by"]) ?>"><?= e($access["reference_no"]) ?> · <?= e(formatDisplayDate($access["granted_at"])) ?></a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php elseif ($records === []): ?>
        <div class="summary-card-empty">No access requests matched the current filters.</div>
        <?php else: ?>
        <div class="summary-card-list">
            <?php foreach ($records as $row): ?>
                <?php
                $referenceNo = trim((string) ($row["reference_no"] ?? ""));
                $statusValue = trim((string) ($row["status"] ?? ""));
                $reviewUrl = buildUrl("access_request_view.php", [
                    "company" => $company["key"],
                    "id" => (int) ($row["id"] ?? 0),
                ]);
                $metaParts = array_filter([
                    trim((string) ($row["dealer"] ?? "")),
                    trim((string) ($row["department"] ?? "")),
                ]);
                $cardFields = [
                    "DMIS username" => $row["dmis_username"] ?? "",
                    "Modules requested" => $row["module"] ?? "",
                    "Requested by" => $row["requested_by"] ?? "",
                    "Submitted" => formatDisplayTimestamp($row["created_at"] ?? null),
                    "IT review by" => $row["it_reviewed_by"] ?? "",
                    "Final decision" => $row["final_decision"] ?? "",
                    "Approved modules" => implode(", ", array_keys(decodeAccessRequestGrantAccess($row["approved_access"] ?? null))),
                    "Final reviewed by" => $row["final_reviewed_by"] ?? "",
                    "Implemented by" => $row["implemented_by"] ?? "",
                ];
                ?>
            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-card-main">
                        <a href="<?= e($reviewUrl) ?>" class="dashboard-activity-id"><?= e($referenceNo !== "" ? $referenceNo : "NO REFERENCE") ?></a>
                        <div class="dashboard-activity-title"><?= e($row["requester_name"] ?? "") ?></div>
                        <?php if ($metaParts !== []): ?>
                        <div class="dashboard-activity-meta"><?= e(implode(" / ", $metaParts)) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="summary-card-action">
                        <span class="status-pill status-pill-<?= e(getAccessRequestStatusClass($statusValue)) ?>"><?= e($statusValue !== "" ? $statusValue : "Unknown") ?></span>
                        <a href="<?= e($reviewUrl) ?>" class="button-link secondary icon-button" aria-label="Review <?= e($referenceNo) ?>" title="Review request">
                            <?= iconSvg("edit") ?>
                            <span class="sr-only">Review request</span>
                        </a>
                    </div>
                </div>

                <div class="summary-card-grid">
                    <?php foreach ($cardFields as $label => $value): ?>
                    <div class="summary-card-field">
                        <div class="summary-card-label"><?= e($label) ?></div>
                        <div class="summary-card-value"><?= e(trim((string) $value) !== "" ? $value : "N/A") ?></div>
                    </div>
                    <?php endforeach; ?>
                    <div class="summary-card-field summary-card-field-full">
                        <div class="summary-card-label">Description</div>
                        <div class="summary-card-value summary-card-value-multiline"><?= nl2br(e($row["description"] ?? "")) ?></div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($pagination["total_pages"] > 1): ?>
        <nav class="pagination" aria-label="<?= $isAccessView ? "User access pages" : "Access request pages" ?>">
            <?php if ($pagination["has_previous"]): ?>
            <a href="<?= e(buildUrl("access_requests.php", $accessRequestQueryParams, ["page" => $pagination["page"] - 1]) . $accessRequestSummaryAnchor) ?>" class="button-link secondary icon-button" aria-label="Previous page" title="Previous page">
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
                    href="<?= e(buildUrl("access_requests.php", $accessRequestQueryParams, ["page" => $pageNumber]) . $accessRequestSummaryAnchor) ?>"
                    class="page-number<?= $pageNumber === $pagination["page"] ? " active" : "" ?>"
                    <?= $pageNumber === $pagination["page"] ? 'aria-current="page"' : "" ?>
                ><?= e($pageNumber) ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($pagination["has_next"]): ?>
            <a href="<?= e(buildUrl("access_requests.php", $accessRequestQueryParams, ["page" => $pagination["page"] + 1]) . $accessRequestSummaryAnchor) ?>" class="button-link secondary icon-button" aria-label="Next page" title="Next page">
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
</main>

<script src="assets/js/index.js" defer></script>
</body>
</html>
