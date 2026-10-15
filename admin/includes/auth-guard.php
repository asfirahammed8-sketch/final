<?php
/**
 * Admin Authentication Guard
 * Protects all administrative routes and handles session expiry
 */

require_once __DIR__ . '/../config.php';

$auth = current_admin();

if (!$auth) {
    // Store requested URI to redirect back after login
    $_SESSION['admin_redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . admin_url('login'));
    exit;
}

// Session Idle Timeout enforcement (60 minutes = 3600 seconds)
$idleLimit = 3600;
if (!empty($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $idleLimit)) {
    // Session expired
    unset($_SESSION['admin_auth']);
    unset($_SESSION['admin_last_activity']);
    $_SESSION['admin_flash_error'] = 'Session timed out due to inactivity. Please log in again.';
    header('Location: ' . admin_url('login'));
    exit;
}

$_SESSION['admin_last_activity'] = time();
