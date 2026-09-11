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
$companyDealerOptions = $company["access_request_dealers"] ?? [];
$filterOptions = [
    "dealer" => $companyDealerOptions,
    "status" => $accessRequestStatusOptions,
    "per_page" => $rowsPerPageOptions,
];

$filters = buildAccessRequestFilters($_GET, $filterOptions);
$totalRecords = countAccessRequests($pdo, $accessRequestTableNameSql, $filters);
$pagination = buildPaginationState($filters["page"], $filters["per_page"], $totalRecords);
$filters["page"] = $pagination["page"];
$records = fetchAccessRequests(
    $pdo,
    $accessRequestTableNameSql,
    $filters,
    $accessRequestStatusOptions,
    $pagination["limit"],
    $pagination["offset"]
);
$statusCounts = countAccessRequestsByStatus($pdo, $accessRequestTableNameSql, $accessRequestStatusOptions);
$accessRequestQueryParams = buildMonitoringListQueryParams($company["key"], $filters);
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
$clearFiltersUrl = buildUrl("access_requests.php", ["company" => $company["key"]]);
$accessRequestSummaryAnchor = "#access-request-summary";
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
            <?php $isActiveStatus = $filters["status"] === $statusOption; ?>
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
                <h2>DMIS Access Requests</h2>
            </div>
            <a href="public_access_request.php" class="button-link secondary icon-button" target="_blank" rel="noopener" aria-label="Open public access request form" title="Open public access request form">
                <?= iconSvg("external-link") ?>
                <span class="sr-only">Open public access request form</span>
            </a>
        </div>

        <form action="access_requests.php#access-request-summary" method="GET" class="summary-filter-form">
            <input type="hidden" name="company" value="<?= e($company["key"]) ?>">

            <div class="summary-filter-grid">
                <div class="field">
                    <label for="access-request-search">Request search</label>
                    <input type="search" id="access-request-search" name="q" value="<?= e($filters["search"]) ?>" placeholder="Reference, name, username, module, or department">
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

                <div class="field">
                    <label for="access-request-status">Status</label>
                    <select id="access-request-status" name="status">
                        <option value="">All statuses</option>
                        <?php foreach ($accessRequestStatusOptions as $option): ?>
                        <option value="<?= e($option) ?>"<?= $filters["status"] === $option ? " selected" : "" ?>><?= e($option) ?></option>
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
                    <a href="<?= e($clearFiltersUrl . $accessRequestSummaryAnchor) ?>" class="button-link secondary icon-button" aria-label="Clear filters" title="Clear filters">
                        <?= iconSvg("x") ?>
                        <span class="sr-only">Clear filters</span>
                    </a>
                </div>
<br><br><br>
                <div class="results-meta">
                    <strong><?= e($pagination["start_item"]) ?>-<?= e($pagination["end_item"]) ?></strong> of <strong><?= e($totalRecords) ?></strong> access requests
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

        <?php if ($records === []): ?>
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
                    trim((string) ($row["module"] ?? "")),
                ]);
                $cardFields = [
                    "DMIS username" => $row["dmis_username"] ?? "",
                    "Submitted" => formatDisplayTimestamp($row["created_at"] ?? null),
                    "Reviewed by" => $row["reviewed_by"] ?? "",
                    "Reviewed at" => formatDisplayTimestamp($row["reviewed_at"] ?? null),
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
        <nav class="pagination" aria-label="Access request pages">
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
