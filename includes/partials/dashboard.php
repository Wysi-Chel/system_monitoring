<?php
$dashboardMetrics = $dashboardData["metrics"] ?? [];
$dashboardUserErrorUrl = buildUrl("index.php", $listQueryParams, [
    "data_correction" => 1,
    "escalation" => null,
    "page" => 1,
]) . "#summary-section";
$dashboardEscalationUrl = buildUrl("index.php", $listQueryParams, [
    "data_correction" => null,
    "escalation" => 1,
    "page" => 1,
]) . "#summary-section";
$dashboardCurrentMonth = (string) ($dashboardData["current_month"] ?? "");
$dashboardMonthlyUrl = buildUrl("index.php", $listQueryParams, [
    "month_from" => $dashboardCurrentMonth,
    "month_to" => null,
    "day" => null,
    "page" => 1,
]) . "#summary-section";

$renderDashboardBreakdown = static function (array $items, string $emptyMessage): void {
    if ($items === []) {
        echo '<p class="dashboard-empty-state">' . e($emptyMessage) . '</p>';
        return;
    }

    echo '<div class="dashboard-breakdown-list">';
    foreach ($items as $item) {
        echo '<div class="dashboard-breakdown-row">';
        echo '<div class="dashboard-breakdown-head">';
        echo '<span class="dashboard-breakdown-label">' . e($item["label"]) . '</span>';
        echo '<span class="dashboard-breakdown-value">' . e(number_format((int) $item["count"])) . '</span>';
        echo '</div>';
        echo '<div class="dashboard-breakdown-track" aria-hidden="true">';
        echo '<span class="dashboard-breakdown-fill" style="width: ' . e((string) $item["bar_width"]) . '%;"></span>';
        echo '</div>';
        echo '<div class="dashboard-breakdown-foot">' . e($item["percentage_label"]) . ' of current scope</div>';
        echo '</div>';
    }
    echo '</div>';
};
?>
<section class="card dashboard-shell" id="dashboard-section">

    <div class="dashboard-scope">
        <div class="dashboard-scope-label">Current scope</div>
        <?php if ($activeFilterBadges !== []): ?>
        <div class="active-filters dashboard-filter-strip" aria-label="Dashboard scope filters">
            <?php foreach ($activeFilterBadges as $badge): ?>
            <span class="filter-badge"><?= e($badge) ?></span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="active-filters dashboard-filter-strip" aria-label="Dashboard scope filters">
            <span class="filter-badge">All records</span>
        </div>
        <?php endif; ?>
    </div>

    <?php
    $accessRequestNotificationQueues = [
        "Pending" => [
            "title" => "New Access Requests",
            "note" => "Waiting for IT review",
            "time_label" => "Submitted",
            "time_key" => "created_at",
        ],
        "For Approval" => [
            "title" => "Access For Approval",
            "note" => "Requested by IT, waiting for final review",
            "time_label" => "Sent",
            "time_key" => "it_reviewed_at",
        ],
        "Approved" => [
            "title" => "Approved Access",
            "note" => "Waiting for IT to implement",
            "time_label" => "Approved",
            "time_key" => "final_reviewed_at",
        ],
    ];
    ?>
    <div class="dashboard-notification-grid" aria-label="Access request notifications">
        <?php foreach ($accessRequestNotificationQueues as $queueStatus => $queue): ?>
            <?php
            $queueCount = (int) ($accessRequestNotifications[$queueStatus]["count"] ?? 0);
            $queueRecords = $accessRequestNotifications[$queueStatus]["records"] ?? [];
            $queueUrl = buildUrl("access_requests.php", [
                "company" => $company["key"],
                "status" => $queueStatus,
            ]) . "#access-request-summary";
            ?>
        <article class="dashboard-panel dashboard-notification<?= $queueCount > 0 ? " has-items" : "" ?>">
            <div class="dashboard-panel-header">
                <div>
                    <h3><?= e($queue["title"]) ?></h3>
                    <p><?= e($queue["note"]) ?></p>
                </div>
                <a href="<?= e($queueUrl) ?>" class="dashboard-notification-count" aria-label="<?= e(number_format($queueCount) . " " . strtolower($queue["title"])) ?>" title="Open <?= e(strtolower($queue["title"])) ?>"><?= e(number_format($queueCount)) ?></a>
            </div>

            <?php if ($queueRecords === []): ?>
            <p class="dashboard-empty-state">Nothing waiting right now.</p>
            <?php else: ?>
            <div class="dashboard-activity-list">
                <?php foreach ($queueRecords as $queueRecord): ?>
                <a href="<?= e(buildUrl("access_request_view.php", ["company" => $company["key"], "id" => (int) $queueRecord["id"]])) ?>" class="dashboard-activity-item dashboard-notification-item">
                    <div class="dashboard-activity-main">
                        <div class="dashboard-activity-id"><?= e($queueRecord["reference_no"]) ?></div>
                        <div class="dashboard-activity-title"><?= e($queueRecord["requester_name"]) ?></div>
                        <div class="dashboard-activity-meta"><?= e(implode(" / ", array_filter([trim((string) $queueRecord["dealer"]), trim((string) $queueRecord["module"])]))) ?></div>
                    </div>
                    <div class="dashboard-activity-meta dashboard-notification-time"><?= e($queue["time_label"]) ?> <?= e(formatDisplayTimestamp($queueRecord[$queue["time_key"]] ?? null)) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if ($queueCount > count($queueRecords)): ?>
            <a href="<?= e($queueUrl) ?>" class="dashboard-panel-note dashboard-notification-more">View all <?= e(number_format($queueCount)) ?> requests</a>
            <?php endif; ?>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-metrics-grid">
        <article class="dashboard-metric-card dashboard-metric-total">
            <span class="dashboard-metric-icon"><?= iconSvg("file-text") ?></span>
            <span class="dashboard-metric-copy">
                <span class="dashboard-metric-label">Total records</span>
                <strong class="dashboard-metric-value"><?= e(number_format((int) ($dashboardMetrics["total_records"] ?? 0))) ?></strong>
                <small class="dashboard-metric-note">Current scope</small>
            </span>
        </article>

        <a href="<?= e($dashboardMonthlyUrl) ?>" class="dashboard-metric-card dashboard-metric-link dashboard-metric-monthly" title="<?= e((string) ($dashboardData["current_month_label"] ?? "Current month")) ?>">
            <span class="dashboard-metric-icon"><?= iconSvg("calendar") ?></span>
            <span class="dashboard-metric-copy">
                <span class="dashboard-metric-label">Monthly records</span>
                <strong class="dashboard-metric-value"><?= e(number_format((int) ($dashboardMetrics["monthly_records"] ?? 0))) ?></strong>
                <small class="dashboard-metric-note"><?= e((string) ($dashboardData["current_month_label"] ?? "Current month")) ?></small>
            </span>
        </a>

        <a href="<?= e($dashboardUserErrorUrl) ?>" class="dashboard-metric-card dashboard-metric-link dashboard-metric-correction">
            <span class="dashboard-metric-icon"><?= iconSvg("edit") ?></span>
            <span class="dashboard-metric-copy">
                <span class="dashboard-metric-label">User error</span>
                <strong class="dashboard-metric-value"><?= e(number_format((int) ($dashboardMetrics["data_correction_records"] ?? 0))) ?></strong>
                <small class="dashboard-metric-note">Needs correction</small>
            </span>
        </a>

        <a href="<?= e($dashboardEscalationUrl) ?>" class="dashboard-metric-card dashboard-metric-link dashboard-metric-escalation">
            <span class="dashboard-metric-icon"><?= iconSvg("upload") ?></span>
            <span class="dashboard-metric-copy">
                <span class="dashboard-metric-label">Action items</span>
                <strong class="dashboard-metric-value"><?= e(number_format((int) ($dashboardMetrics["escalation_records"] ?? 0))) ?></strong>
                <small class="dashboard-metric-note">Needs attention</small>
            </span>
        </a>

        <article class="dashboard-metric-card dashboard-metric-tickets">
            <span class="dashboard-metric-icon"><?= iconSvg("ticket") ?></span>
            <span class="dashboard-metric-copy">
                <span class="dashboard-metric-label">Linked tickets</span>
                <strong class="dashboard-metric-value"><?= e(number_format((int) ($dashboardMetrics["linked_tickets"] ?? 0))) ?></strong>
                <small class="dashboard-metric-note">Connected records</small>
            </span>
        </article>
    </div>

    <div class="dashboard-panel-grid">
        <article class="dashboard-panel dashboard-panel-wide">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Status Overview</h3>
                </div>
            </div>
            <?php $renderDashboardBreakdown($dashboardData["status_breakdown"] ?? [], "No status tags are available for this scope."); ?>
        </article>


        <?php if ($ticketDashboardData !== null): ?>
        <article class="dashboard-panel dashboard-panel-wide dashboard-ticket-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Ticket Snapshot</h3>
                </div>
                <a href="<?= e($ticketMonitoringUrl) ?>" class="button-link button-with-icon secondary">
                    <?= iconSvg("ticket") ?>
                    <span>Open Ticket Monitoring</span>
                </a>
            </div>

            <div class="dashboard-ticket-metrics">
                <div class="dashboard-ticket-metric">
                    <span class="dashboard-ticket-label">Total tickets</span>
                    <strong><?= e(number_format((int) ($ticketDashboardData["metrics"]["total_tickets"] ?? 0))) ?></strong>
                </div>
                    <div class="dashboard-ticket-metric">
                        <span class="dashboard-ticket-label">Active</span>
                    <strong><?= e(number_format((int) ($ticketDashboardData["metrics"]["active_tickets"] ?? 0))) ?></strong>
                </div>
                <div class="dashboard-ticket-metric">
                    <span class="dashboard-ticket-label">Resolved</span>
                    <strong><?= e(number_format((int) ($ticketDashboardData["metrics"]["resolved_tickets"] ?? 0))) ?></strong>
                </div>
                <div class="dashboard-ticket-metric">
                    <span class="dashboard-ticket-label">7+ days old</span>
                    <strong><?= e(number_format((int) ($ticketDashboardData["metrics"]["aging_tickets"] ?? 0))) ?></strong>
                </div>
            </div>

            <p class="dashboard-ticket-note">
                Oldest active ticket:
                <strong><?= e(number_format((int) ($ticketDashboardData["metrics"]["oldest_active_days"] ?? 0))) ?> day(s)</strong>
            </p>

            <?php $renderDashboardBreakdown($ticketDashboardData["status_breakdown"] ?? [], "No ticket records are available for this scope."); ?>
        </article>
        <?php endif; ?>

    </div>
</section>
