<?php
require __DIR__ . "/includes/auth.php";

if (isMonitoringAuthenticated()) {
    header("Location: index.php");
    exit;
}

header("Location: " . MONITORING_PORTAL_LOGIN_URL);
exit;
