<?php
/**
 * DreamIT Admin Console - Core Configuration & Bootstrap
 * Auto-detects local development vs live production environment
 */

if (session_status() === PHP_SESSION_NONE) {
    // Hardened session configuration
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', __DIR__);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/helpers.php';

// Auto-detect Admin Slug from environment or default
function get_admin_slug(): string {
    static $slug = null;
    if ($slug !== null) return $slug;

    // Check .env
    $envSlug = getenv('ADMIN_SLUG') ?: ($_ENV['ADMIN_SLUG'] ?? null);
    if (!empty($envSlug)) {
        $slug = trim($envSlug, '/');
        return $slug;
    }

    // Fallback default
    $slug = 'cpanel-dreamit';
    return $slug;
}

// Generate an admin URL that dynamically adapts to production vs localhost
function admin_url(string $path = ''): string {
    $slug = get_admin_slug();
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return base_url($slug . '/');
    }
    return base_url($slug . '/' . $cleanPath);
}

// Helper to determine if in development mode
function is_admin_dev_mode(): bool {
    $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');
    if ($env === 'development') return true;
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($remoteIp, ['127.0.0.1', '::1']);
}

// Generate or fetch CSRF token for Admin AJAX requests
function admin_csrf_token(): string {
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

// Validate CSRF token
function verify_admin_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['admin_csrf'])) {
        return false;
    }
    return hash_equals($_SESSION['admin_csrf'], $token);
}

// Get logged-in admin user record
function current_admin(): ?array {
    return $_SESSION['admin_auth'] ?? null;
}
