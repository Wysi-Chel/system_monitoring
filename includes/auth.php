<?php
const MONITORING_PORTAL_LOGIN_URL = "/micei_mis/login.php";
const MONITORING_PORTAL_SESSION_USER_KEY = "user";
const MONITORING_PORTAL_SUPER_ADMIN_ROLE = "Super Administrator";

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

function getMonitoringPortalUser(): array
{
    startMonitoringSession();
    $portalUser = $_SESSION[MONITORING_PORTAL_SESSION_USER_KEY] ?? [];
    return is_array($portalUser) ? $portalUser : [];
}

function getMonitoringPortalUsername(): string
{
    return trim((string) (getMonitoringPortalUser()["username"] ?? ""));
}

function isMonitoringAuthenticated(): bool
{
    return !empty(getMonitoringPortalUser()["id"]);
}

function isMonitoringSuperAdmin(): bool
{
    return trim((string) (getMonitoringPortalUser()["role"] ?? "")) === MONITORING_PORTAL_SUPER_ADMIN_ROLE;
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
