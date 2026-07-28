<?php
$headerKicker = $headerKicker ?? $company["company_name"];
$headerTitle = $headerTitle ?? $company["system_name"];
$currentScript = basename((string) ($_SERVER["SCRIPT_NAME"] ?? "index.php"));
$defaultHeaderDescriptions = [
    "index.php" => "Track daily transactions, exceptions, and action items in one workspace.",
    "ticket_monitoring.php" => "Create, review, and follow support tickets from submission to resolution.",
    "monitoring_record.php" => "Review the complete activity, supporting details, and actions for a monitoring record.",
    "promote_to_live.php" => "Review test changes before promoting them to the live monitoring system.",
];
$headerDescription = $headerDescription ?? ($defaultHeaderDescriptions[$currentScript] ?? "");
$showCompanySwitch = $showCompanySwitch ?? true;
$appEnvironmentLabel = getApplicationEnvironmentDisplayLabel();
$todayDisplay = (new DateTimeImmutable("now", new DateTimeZone("Asia/Manila")))->format("M d, Y");
$portalUser = $_SESSION[MONITORING_PORTAL_SESSION_USER_KEY] ?? [];
$portalUserName = trim((string) ($portalUser["full_name"] ?? $portalUser["name"] ?? "Portal user"));
$portalUserRole = trim((string) ($portalUser["role"] ?? "User"));
$portalUserInitial = uppercaseText(substr($portalUserName !== "" ? $portalUserName : "U", 0, 1));

$monitoringHomeUrl = buildUrl("index.php", ["company" => $company["key"]]);
$encodeRecordUrl = $currentScript === "index.php"
    ? "#encode-section"
    : $monitoringHomeUrl . "#encode-section";
$summaryUrl = $currentScript === "index.php"
    ? "#summary-section"
    : $monitoringHomeUrl . "#summary-section";
$ticketNavUrl = $ticketMonitoringUrl ?? buildUrl("ticket_monitoring.php", ["company" => $company["key"]]);
$promotionUrl = buildUrl("promote_to_live.php", ["company" => $company["key"]]);
$topbarActionUrl = $encodeRecordUrl;
$topbarActionLabel = "Encode record";
$topbarActionIcon = "plus";
$sidebarPrimaryActionUrl = $encodeRecordUrl;
$sidebarPrimaryActionLabel = "Encode New Record";
$sidebarSummaryActionUrl = $summaryUrl;

if ($currentScript === "ticket_monitoring.php") {
    $topbarActionUrl = "#ticket-record-form";
    $topbarActionLabel = "New ticket record";
    $topbarActionIcon = "plus";
    $sidebarPrimaryActionUrl = "#ticket-record-form";
    $sidebarPrimaryActionLabel = "Encode Ticket Record";
    $sidebarSummaryActionUrl = "#ticket-summary";
} elseif ($currentScript === "monitoring_record.php") {
    $topbarActionUrl = $summaryUrl;
    $topbarActionLabel = "Back to summary";
    $topbarActionIcon = "arrow-left";
} elseif ($currentScript === "promote_to_live.php") {
    $topbarActionUrl = $monitoringHomeUrl;
    $topbarActionLabel = "Back to dashboard";
    $topbarActionIcon = "arrow-left";
}

$navItems = [
    [
        "label" => "Dashboard",
        "icon" => "home",
        "href" => $monitoringHomeUrl,
        "script" => "index.php",
        "scripts" => ["index.php", "monitoring_record.php"],
    ],
    [
        "label" => "Ticket Monitoring",
        "icon" => "ticket",
        "href" => $ticketNavUrl,
        "script" => "ticket_monitoring.php",
        "visible" => companySupportsTicketMonitoring($company),
    ],
    [
        "label" => "Promote To Live",
        "icon" => "upload",
        "href" => $promotionUrl,
        "script" => "promote_to_live.php",
        "visible" => canAccessPromoteToLiveUi(),
    ],
];
?>
<aside class="app-sidebar" id="app-sidebar">
    <div class="sidebar-header">
        <a href="<?= e($monitoringHomeUrl) ?>" class="sidebar-brand" aria-label="<?= e($company["system_name"]) ?> home">
            <span class="sidebar-brand-mark">
                <img src="<?= e($company["logo_path"]) ?>" alt="">
            </span>
            <span class="sidebar-brand-copy">
                <span class="brand-kicker"><?= e($company["company_name"]) ?></span>
                <strong class="brand-title">System Monitoring</strong>
            </span>
        </a>
    </div>

    <?php if ($showCompanySwitch): ?>

    <section class="sidebar-panel sidebar-company-panel">
        <div class="sidebar-panel-label">Company Workspace</div>
        <div class="company-switch" aria-label="Switch company">
            <a href="<?= e($mitsubishiUrl) ?>" class="switch-link<?= $company["key"] === "mitsubishi" ? " active" : "" ?>" title="Mitsubishi"<?= $company["key"] === "mitsubishi" ? ' aria-current="page"' : "" ?>>
                <span class="switch-link-label">Mitsubishi</span>
                <span class="switch-link-short" aria-hidden="true">M</span>
            </a>
            <a href="<?= e($hyundaiUrl) ?>" class="switch-link<?= $company["key"] === "hyundai" ? " active" : "" ?>" title="Hyundai"<?= $company["key"] === "hyundai" ? ' aria-current="page"' : "" ?>>
                <span class="switch-link-label">Hyundai</span>
                <span class="switch-link-short" aria-hidden="true">H</span>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <section class="sidebar-panel sidebar-workspace-panel">
        <div class="sidebar-panel-label">Workspace</div>
        <nav class="sidebar-nav" aria-label="Primary navigation">
            <?php foreach ($navItems as $item): ?>
                <?php if (array_key_exists("visible", $item) && !$item["visible"]): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <?php $isActiveNavItem = in_array($currentScript, $item["scripts"] ?? [$item["script"]], true); ?>
                <a
                    href="<?= e($item["href"]) ?>"
                    class="sidebar-link<?= $isActiveNavItem ? " active" : "" ?>"
                    title="<?= e($item["label"]) ?>"
                    <?= $isActiveNavItem ? 'aria-current="page"' : "" ?>
                >
                    <?= iconSvg((string) ($item["icon"] ?? "home")) ?>
                    <span><?= e($item["label"]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="sidebar-action">
        <div class="sidebar-panel-label">Quick Action</div>
        <a href="<?= e($sidebarPrimaryActionUrl) ?>" class="button-link button-with-icon primary sidebar-primary-action" title="<?= e($sidebarPrimaryActionLabel) ?>">
            <?= iconSvg("plus") ?>
            <span><?= e($sidebarPrimaryActionLabel) ?></span>
        </a>
        <a href="<?= e($sidebarSummaryActionUrl) ?>" class="button-link button-with-icon secondary sidebar-secondary-action" title="Open summary">
            <?= iconSvg("file-text") ?>
            <span>Open Summary</span>
        </a>
    </section>

    <div class="sidebar-footer">
        <div class="sidebar-theme-control">
            <span class="sidebar-theme-label">Appearance</span>
            <button type="button" class="theme-toggle theme-switch" id="theme-toggle" aria-pressed="false" aria-label="Switch to dark mode" title="Switch to dark mode">
                <span class="theme-switch-icon theme-switch-sun"><?= iconSvg("sun") ?></span>
                <span class="theme-switch-icon theme-switch-moon"><?= iconSvg("moon") ?></span>
                <span class="theme-switch-thumb" aria-hidden="true"></span>
                <span class="sr-only">Switch theme</span>
            </button>
        </div>
        <div class="sidebar-user">
            <span class="user-avatar"><?= e($portalUserInitial) ?></span>
            <span class="sidebar-user-copy">
                <strong><?= e($portalUserName !== "" ? $portalUserName : "Portal user") ?></strong>
                <small><?= e($portalUserRole !== "" ? $portalUserRole : "User") ?> · <?= e($todayDisplay) ?></small>
            </span>
        </div>
        <a
            href="/micei_mis/systems.php"
            class="sidebar-launcher-link"
            title="Return to system launcher"
        >
            <?= iconSvg("arrow-left") ?>
            <span>System Launcher</span>
        </a>
    </div>

    <button
        type="button"
        class="sidebar-toggle"
        id="sidebar-toggle"
        aria-controls="app-sidebar"
        aria-expanded="true"
        aria-label="Collapse sidebar"
        title="Collapse sidebar"
    >
        <?= iconSvg("chevrons-left") ?>
    </button>
</aside>

<button type="button" class="sidebar-scrim" id="sidebar-scrim" aria-label="Close navigation" hidden></button>

<header class="app-topbar">
    <div class="topbar-copy">
        <p class="eyebrow">IT Department</p>
        <h1 class="page-title"><?= e($headerTitle) ?></h1>
        <?php if ($headerDescription !== ""): ?>
        <p class="page-description"><?= e($headerDescription) ?></p>
        <?php endif; ?>
    </div>
    <div class="topbar-actions">
        <button
            type="button"
            class="mobile-sidebar-toggle"
            id="mobile-sidebar-toggle"
            aria-controls="app-sidebar"
            aria-expanded="false"
        >
            <?= iconSvg("home") ?>
            <span>Menu</span>
        </button>
        <a href="<?= e($topbarActionUrl) ?>" class="button-link button-with-icon primary topbar-primary-action">
            <?= iconSvg($topbarActionIcon) ?>
            <span><?= e($topbarActionLabel) ?></span>
        </a>
        <?php if (canAccessPromoteToLiveUi()): ?>
        <a href="<?= e($promotionUrl) ?>" class="button-link secondary topbar-inline-action icon-button" aria-label="Promote to live" title="Promote to live">
            <?= iconSvg("upload") ?>
            <span class="sr-only">Promote to live</span>
        </a>
        <?php endif; ?>
    </div>
</header>
