<?php
/**
 * CSRF (Cross-Site Request Forgery) Protection
 *
 * Generates and validates cryptographically secure tokens
 * Prevents unauthorized state-changing requests
 */

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function csrf_generate_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Generate new token if not exists or expired
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    // Rotate token every 1 hour
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Get current CSRF token
 *
 * @return string CSRF token
 */
function csrf_token(): string {
    return csrf_generate_token();
}

/**
 * Validate CSRF token from request
 *
 * @param string|null $token Token to validate (if null, auto-detect from request)
 * @return bool True if valid, false otherwise
 */
function csrf_validate(?string $token = null): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Get token from request if not provided
    if ($token === null) {
        // Check POST data
        $token = $_POST['csrf_token'] ?? null;

        // Check HTTP header (for AJAX requests)
        if ($token === null && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
    }

    // No token provided
    if ($token === null || !is_string($token)) {
        return false;
    }

    // No session token exists
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Token expired (older than 2 hours)
    if (!isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time'] > 7200)) {
        return false;
    }

    // Constant-time comparison to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token or die with 403
 * Use this at the start of state-changing endpoints
 */
function csrf_require(): void {
    if (!csrf_validate()) {
        http_response_code(403);

        if (defined('API_REQUEST') && API_REQUEST) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'CSRF_VALIDATION_FAILED',
                'message' => 'Invalid or expired CSRF token. Please refresh the page and try again.'
            ]);
        } else {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Security Error</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .error { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 500px; text-align: center; }
        h1 { color: #e53e3e; margin: 0 0 16px; font-size: 24px; }
        p { color: #4a5568; margin: 0 0 24px; line-height: 1.6; }
        button { background: #3182ce; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; }
        button:hover { background: #2c5282; }
    </style>
</head>
<body>
    <div class="error">
        <h1>🔒 Security Validation Failed</h1>
        <p>Your request could not be verified. This usually happens when:</p>
        <ul style="text-align: left; color: #718096; line-height: 1.8;">
            <li>Your session has expired</li>
            <li>You opened the page in multiple tabs</li>
            <li>You navigated back after submitting a form</li>
        </ul>
        <button onclick="window.location.reload()">Refresh Page</button>
    </div>
</body>
</html>';
        }
        exit;
    }
}

/**
 * Generate HTML hidden input field with CSRF token
 * Use this in forms
 *
 * @return string HTML input field
 */
function csrf_field(): string {
    $token = e(csrf_token());
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Generate CSRF token meta tag for JavaScript access
 * Use this in <head> section
 *
 * @return string HTML meta tag
 */
function csrf_meta(): string {
    $token = e(csrf_token());
    return '<meta name="csrf-token" content="' . $token . '">';
}

/**
 * Get CSRF token for JavaScript
 * Call this to get token value for AJAX requests
 *
 * @return string JavaScript code that sets global CSRF token
 */
function csrf_script(): string {
    $token = json_encode(csrf_token());
    return '<script>window.CSRF_TOKEN = ' . $token . ';</script>';
}
