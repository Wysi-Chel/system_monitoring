<?php
$headerKicker = $headerKicker ?? $company["company_name"];
$headerTitle = $headerTitle ?? $company["system_name"];
$headerDescription = $headerDescription ?? "";
$showCompanySwitch = $showCompanySwitch ?? true;
$appEnvironmentLabel = getApplicationEnvironmentDisplayLabel();
$currentScript = basename((string) ($_SERVER["SCRIPT_NAME"] ?? "index.php"));
$todayDisplay = (new DateTimeImmutable("now", new DateTimeZone("Asia/Manila")))->format("M d, Y");

$monitoringHomeUrl = buildUrl("index.php", ["company" => $company["key"]]);
$encodeRecordUrl = $currentScript === "index.php"
    ? "#encode-section"
    : $monitoringHomeUrl . "#encode-section";
$summaryUrl = $currentScript === "index.php"
    ? "#summary-section"
    : $monitoringHomeUrl . "#summary-section";
$ticketNavUrl = $ticketMonitoringUrl ?? buildUrl("ticket_monitoring.php", ["company" => $company["key"]]);
$promotionUrl = buildUrl("promote_to_live.php", ["company" => $company["key"]]);

$navItems = [
    [
        "label" => "Dashboard",
        "icon" => "home",
        "href" => $monitoringHomeUrl,
        "script" => "index.php",
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
                <a
                    href="<?= e($item["href"]) ?>"
                    class="sidebar-link<?= $currentScript === $item["script"] ? " active" : "" ?>"
                    title="<?= e($item["label"]) ?>"
                    <?= $currentScript === $item["script"] ? 'aria-current="page"' : "" ?>
                >
                    <?= iconSvg((string) ($item["icon"] ?? "home")) ?>
                    <span><?= e($item["label"]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="sidebar-action">
        <div class="sidebar-panel-label">Quick Action</div>
        <a href="<?= e($encodeRecordUrl) ?>" class="button-link button-with-icon primary sidebar-primary-action" title="Encode new record">
            <?= iconSvg("plus") ?>
            <span>Encode New Record</span>
        </a>
        <a href="<?= e($summaryUrl) ?>" class="button-link button-with-icon secondary sidebar-secondary-action" title="Open summary">
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
        <div class="sidebar-note">
            <span class="sidebar-note-icon"><?= iconSvg("calendar") ?></span>
            <span class="sidebar-note-copy">
                <span>Today</span>
                <strong><?= e($todayDisplay) ?></strong>
            </span>
        </div>
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

<?php if (canAccessPromoteToLiveUi()): ?>
<header class="app-topbar">
    <div class="topbar-meta">
        <a href="<?= e($promotionUrl) ?>" class="button-link secondary topbar-inline-action icon-button" aria-label="Promote to live" title="Promote to live">
            <?= iconSvg("upload") ?>
            <span class="sr-only">Promote to live</span>
        </a>
    </div>
</header>
<?php endif; ?>
