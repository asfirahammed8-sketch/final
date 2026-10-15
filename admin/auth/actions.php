<?php
/**
 * DreamIT Admin Console - Auth Controller & Action Handlers
 * Production-grade: Real-time PHPMailer SMTP OTP, NO bypass, brute-force protection,
 * CSRF verification, dual AJAX/Form support, rate limiting, and session hardening.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../config/email.php';

$action   = $_GET['action'] ?? '';
$db       = Database::getConnection();
$clientIp = get_client_ip();

// Capture either form POST or JSON body
$jsonInput = json_decode(file_get_contents('php://input'), true) ?: [];
$postData  = !empty($_POST) ? $_POST : $jsonInput;

// ──────────────────────────────────────────────────────────────────────────────
// Universal Response Helper (Handles both AJAX JSON and Standard POST redirects)
// ──────────────────────────────────────────────────────────────────────────────
function auth_respond(bool $success, string $message, array $extra = [], ?string $redirect = null) {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
        || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success'  => $success,
            'message'  => $message,
            'redirect' => $redirect
        ], $extra));
        exit;
    }

    if (!$success) {
        $_SESSION['admin_login_error'] = $message;
    } else {
        $_SESSION['admin_flash_success'] = $message;
    }

    $dest = $redirect ?: admin_url('login');
    header('Location: ' . $dest);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// Rate-limit helper
// ──────────────────────────────────────────────────────────────────────────────
function admin_rate_check(string $key, int $maxAttempts = 5, int $lockoutSeconds = 900): bool {
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first' => $now, 'locked_until' => 0];
    }
    $r = &$_SESSION[$key];

    if ($r['locked_until'] > $now) {
        $wait = ceil(($r['locked_until'] - $now) / 60);
        $_SESSION['admin_login_error'] = "Too many attempts. Please wait {$wait} minute(s) before trying again.";
        return false;
    }

    if ($now - $r['first'] > $lockoutSeconds) {
        $r = ['count' => 0, 'first' => $now, 'locked_until' => 0];
    }

    $r['count']++;
    if ($r['count'] >= $maxAttempts) {
        $r['locked_until'] = $now + $lockoutSeconds;
        $_SESSION['admin_login_error'] = 'Too many failed attempts. Account temporarily locked for 15 minutes.';
        return false;
    }
    return true;
}

function admin_rate_reset(string $key): void {
    unset($_SESSION[$key]);
}

// ──────────────────────────────────────────────────────────────────────────────
// 1. STEP 1: LOGIN (EMAIL & PASSWORD VERIFICATION)
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize_text($postData['email'] ?? '');
    $password = (string)($postData['password'] ?? '');
    $token    = $postData['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!verify_admin_csrf($token)) {
        auth_respond(false, 'Security token validation failed. Please refresh and try again.');
    }

    // Rate-limit by IP
    $rlKey = 'admin_login_attempts_' . md5($clientIp);
    if (!admin_rate_check($rlKey, 5, 900)) {
        auth_respond(false, $_SESSION['admin_login_error'] ?? 'Too many failed attempts. Please wait 15 minutes.');
    }

    if (empty($email) || empty($password)) {
        auth_respond(false, 'Email and password are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        auth_respond(false, 'Please enter a valid email address.');
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, $admin['password'])) {
        auth_respond(false, 'Invalid email address or password.');
    }

    if (isset($admin['status']) && $admin['status'] !== 'active') {
        auth_respond(false, 'This administrative account has been deactivated. Contact the administrator.');
    }

    // Clear login rate-limit on correct credentials
    admin_rate_reset($rlKey);

    // Generate cryptographic 6-digit OTP
    $otp       = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash   = password_hash($otp, PASSWORD_DEFAULT);

    $updateStmt = $db->prepare("UPDATE admins SET otp_hash = :hash, otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = :id");
    $updateStmt->execute([':hash' => $otpHash, ':id' => $admin['id']]);

    // Send real-time OTP via PHPMailer / Gmail SMTP
    $emailSent = send_otp_email($admin['email'], $otp, $admin['name'] ?? 'Admin');

    if (!$emailSent) {
        auth_respond(false, 'Unable to dispatch real-time OTP email. Please verify SMTP settings in .env.');
    }

    $_SESSION['admin_login_step']    = 'otp';
    $_SESSION['admin_pending_email'] = $admin['email'];
    $_SESSION['admin_pending_name']  = $admin['name'] ?? 'Admin';
    $_SESSION['admin_otp_last_sent'] = time();

    // Ensure dev bypass is strictly unset
    unset($_SESSION['admin_dev_otp']);

    auth_respond(true, 'A 6-digit authentication OTP has been sent in real time to your email.', [
        'step'  => 'otp',
        'email' => $admin['email']
    ], admin_url('login'));
}

// ──────────────────────────────────────────────────────────────────────────────
// 2. RESEND OTP (REALTIME WITH 60-SEC COOLDOWN)
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'resend-otp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $postData['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verify_admin_csrf($token)) {
        auth_respond(false, 'Security token validation failed.');
    }

    $email = $_SESSION['admin_pending_email'] ?? '';
    if (empty($email)) {
        auth_respond(false, 'No active authentication session found. Please sign in again.', [], admin_url('login?step=reset'));
    }

    $lastSent = (int)($_SESSION['admin_otp_last_sent'] ?? 0);
    $elapsed  = time() - $lastSent;
    if ($elapsed < 60) {
        $remaining = 60 - $elapsed;
        auth_respond(false, "Please wait {$remaining} seconds before requesting a new OTP.");
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE email = :email AND status = 'active' LIMIT 1");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        auth_respond(false, 'Administrative account not found.', [], admin_url('login?step=reset'));
    }

    // Generate fresh 6-digit OTP
    $otp       = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash   = password_hash($otp, PASSWORD_DEFAULT);

    $upStmt = $db->prepare("UPDATE admins SET otp_hash = :hash, otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = :id");
    $upStmt->execute([':hash' => $otpHash, ':id' => $admin['id']]);

    // Send real-time OTP via PHPMailer
    $sent = send_otp_email($admin['email'], $otp, $admin['name'] ?? 'Admin');
    if (!$sent) {
        auth_respond(false, 'Failed to send OTP email. Please verify SMTP connection.');
    }

    $_SESSION['admin_otp_last_sent'] = time();
    unset($_SESSION['admin_dev_otp']);

    auth_respond(true, 'A new 6-digit OTP code has been dispatched to your email in real time.');
}

// ──────────────────────────────────────────────────────────────────────────────
// 3. STEP 2: VERIFY OTP
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'verify-otp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize_text($postData['email'] ?? ($_SESSION['admin_pending_email'] ?? ''));
    $otpInput = trim($postData['otp_code'] ?? '');
    $token    = $postData['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!verify_admin_csrf($token)) {
        auth_respond(false, 'Security token validation failed.');
    }

    // Rate-limit OTP guesses (max 5 attempts per 10 minutes)
    $rlOtp = 'admin_otp_attempts_' . md5($email . $clientIp);
    if (!admin_rate_check($rlOtp, 5, 600)) {
        auth_respond(false, 'Too many incorrect OTP attempts. Please wait 10 minutes.');
    }

    // OTP must be exactly 6 digits
    if (!preg_match('/^\d{6}$/', $otpInput)) {
        auth_respond(false, 'Please enter a valid 6-digit numeric OTP.');
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || empty($admin['otp_hash']) || empty($admin['otp_expires_at'])) {
        auth_respond(false, 'Authentication session has expired. Please sign in again.', [], admin_url('login?step=reset'));
    }

    $dbNow = $db->query("SELECT NOW()")->fetchColumn();
    $otpExpTime = strtotime($admin['otp_expires_at']);
    $isOtpExpired = ($otpExpTime < strtotime($dbNow)) && ($otpExpTime < time());

    if ($isOtpExpired) {
        auth_respond(false, 'OTP has expired (10-minute limit exceeded). Please request a new code or sign in again.', [], admin_url('login?step=reset'));
    }

    if (!password_verify($otpInput, $admin['otp_hash'])) {
        auth_respond(false, 'Incorrect OTP code. Please check your email and try again.');
    }

    // Clear rate limit on success
    admin_rate_reset($rlOtp);

    // Invalidate used OTP immediately & update last login timestamp
    $clearStmt = $db->prepare("UPDATE admins SET otp_hash = NULL, otp_expires_at = NULL, last_login = NOW() WHERE id = :id");
    $clearStmt->execute([':id' => $admin['id']]);

    // Regenerate Session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['admin_auth'] = [
        'id'    => $admin['id'],
        'name'  => $admin['name'],
        'email' => $admin['email'],
        'role'  => $admin['role'] ?? 'super_admin'
    ];
    $_SESSION['admin_last_activity'] = time();

    // Clean up login session artifacts
    unset($_SESSION['admin_login_step']);
    unset($_SESSION['admin_pending_email']);
    unset($_SESSION['admin_pending_name']);
    unset($_SESSION['admin_dev_otp']);
    unset($_SESSION['admin_otp_last_sent']);

    $redirect = $_SESSION['admin_redirect_after_login'] ?? admin_url('dashboard');
    unset($_SESSION['admin_redirect_after_login']);

    auth_respond(true, 'Authentication successful! Redirecting to dashboard...', [
        'user' => [
            'name'  => $admin['name'],
            'email' => $admin['email']
        ]
    ], $redirect);
}

// ──────────────────────────────────────────────────────────────────────────────
// 4. FORGOT PASSWORD (REAL-TIME EMAIL)
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'forgot-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_text($postData['email'] ?? '');
    $token = $postData['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!verify_admin_csrf($token)) {
        auth_respond(false, 'CSRF verification failed. Please refresh.');
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        auth_respond(false, 'Please provide a valid administrative email address.');
    }

    // Rate-limit password-reset requests
    $rlReset = 'admin_forgot_attempts_' . md5($clientIp);
    if (!admin_rate_check($rlReset, 4, 1800)) {
        auth_respond(false, 'Too many reset requests. Please wait 30 minutes.');
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE email = :email AND status = 'active' LIMIT 1");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $up = $db->prepare("UPDATE admins SET password_reset_token = :tok, password_reset_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id");
        $up->execute([':tok' => $tokenHash, ':id' => $admin['id']]);

        $resetUrl = admin_url('reset-password?token=' . $rawToken);
        send_password_reset_email($admin['email'], $resetUrl, $admin['name'] ?? 'Admin');
    }

    // Standard anti-enumeration response
    auth_respond(true, 'If that administrative email exists, password reset instructions have been sent in real time.', [], admin_url('forgot-password'));
}

// ──────────────────────────────────────────────────────────────────────────────
// 5. RESET PASSWORD
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'reset-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawToken = trim($postData['token'] ?? '');
    $newPass  = (string)($postData['new_password'] ?? '');
    $confPass = (string)($postData['confirm_password'] ?? '');
    $csrf     = $postData['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!verify_admin_csrf($csrf)) {
        auth_respond(false, 'Invalid security token.');
    }

    if (strlen($newPass) < 8) {
        auth_respond(false, 'Password must be at least 8 characters long.');
    }

    if ($newPass !== $confPass) {
        auth_respond(false, 'Passwords do not match. Please verify both fields.');
    }

    $tokenHash = hash('sha256', $rawToken);
    $stmt = $db->prepare("SELECT * FROM admins WHERE password_reset_token = :tok LIMIT 1");
    $stmt->execute([':tok' => $tokenHash]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $dbNow = $db->query("SELECT NOW()")->fetchColumn();
    $isValid = false;
    if ($admin && !empty($admin['password_reset_expires_at'])) {
        $expTime = strtotime($admin['password_reset_expires_at']);
        if ($expTime >= strtotime($dbNow) || $expTime >= time()) {
            $isValid = true;
        }
    }

    if (!$isValid) {
        auth_respond(false, 'This password reset link is invalid or has expired. Please request a new one.', [], admin_url('forgot-password'));
    }

    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
    $up = $db->prepare("UPDATE admins SET password = :p, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = :id");
    $up->execute([':p' => $newHash, ':id' => $admin['id']]);

    $_SESSION['admin_flash_success'] = 'Password updated successfully! You may now sign in with your new password.';
    auth_respond(true, 'Password updated successfully! Please sign in.', [], admin_url('login'));
}

// ──────────────────────────────────────────────────────────────────────────────
// 6. LOGOUT
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    unset($_SESSION['admin_auth']);
    unset($_SESSION['admin_last_activity']);
    $_SESSION['admin_flash_success'] = 'You have been securely signed out.';
    header('Location: ' . admin_url('login'));
    exit;
}

http_response_code(400);
auth_respond(false, 'Invalid authentication action.');
