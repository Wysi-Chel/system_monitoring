<?php
const MONITORING_PORTAL_LOGIN_URL = "/micei_mis/login.php";
const MONITORING_PORTAL_SESSION_USER_KEY = "user";

function startMonitoringSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "path" => "/",
    ]);
    session_start();
}

function isMonitoringAuthenticated(): bool
{
    startMonitoringSession();
    $portalUser = $_SESSION[MONITORING_PORTAL_SESSION_USER_KEY] ?? null;
    return is_array($portalUser) && !empty($portalUser["id"]);
}

function getSafeAuthRedirectTarget(?string $target): string
{
    $target = trim((string) $target);
    if ($target === "" || str_starts_with($target, "//") || preg_match('/^[a-z][a-z0-9+.-]*:/i', $target)) {
        return "index.php";
    }

    return $target;
}

function requireMonitoringAuthentication(): void
{
    if (isMonitoringAuthenticated()) {
        return;
    }

    header("Location: " . MONITORING_PORTAL_LOGIN_URL);
    exit;
}
