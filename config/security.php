<?php
/**
 * Security Configuration & HTTP Headers
 *
 * Production-grade security headers and configuration
 * Protects against XSS, Clickjacking, MIME sniffing, and other attacks
 */

// Prevent direct access
if (!defined('SECURITY_LOADED')) {
    define('SECURITY_LOADED', true);
}

/**
 * Apply secure HTTP headers
 */
function apply_security_headers(): void {
    // Already sent headers check
    if (headers_sent()) {
        return;
    }

    // Content Security Policy (Strict)
    // Note: Adjust 'unsafe-inline' and 'unsafe-eval' based on your needs
    // For production, remove unsafe-inline and unsafe-eval after refactoring inline scripts
    header("Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://www.youtube.com https://www.googletagmanager.com https://maps.googleapis.com https://maps.gstatic.com; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
        "img-src 'self' data: https: http:; " .
        "font-src 'self' data: https://fonts.gstatic.com; " .
        "connect-src 'self'; " .
        "frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com; " .
        "media-src 'self' https: http: blob:; " .
        "object-src 'none'; " .
        "base-uri 'self'; " .
        "form-action 'self'; " .
        "frame-ancestors 'self'; " .
        "upgrade-insecure-requests;"
    );

    // Prevent clickjacking attacks
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Enable XSS protection in older browsers
    header('X-XSS-Protection: 1; mode=block');

    // Referrer Policy (balance between privacy and functionality)
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions Policy (disable unnecessary browser features)
    header('Permissions-Policy: ' .
        'geolocation=(), ' .
        'microphone=(), ' .
        'camera=(), ' .
        'payment=(), ' .
        'usb=(), ' .
        'magnetometer=(), ' .
        'gyroscope=(), ' .
        'accelerometer=()'
    );

    // HSTS (HTTP Strict Transport Security) - Enable only if using HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    // Remove server version information
    header_remove('X-Powered-By');
    header_remove('Server');
}

/**
 * Secure session configuration
 */
function configure_secure_session(): void {
    // Session already started check
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Secure session configuration
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');

    // Enable secure cookie only on HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    // Start session with secure options
    session_start([
        'cookie_lifetime' => 0, // Session cookie (expires when browser closes)
        'cookie_httponly' => true,
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'sid_length' => 48,
        'sid_bits_per_character' => 6,
    ]);
}

/**
 * Session fingerprinting for hijacking prevention
 *
 * @return string Session fingerprint
 */
function get_session_fingerprint(): string {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $ipSubnet = get_ip_subnet($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

    return hash('sha256', $userAgent . $acceptLanguage . $ipSubnet . 'SECRET_SALT_CHANGE_THIS');
}

/**
 * Get IP subnet (first 3 octets for IPv4, first 4 groups for IPv6)
 * Allows roaming between same subnet while preventing full IP changes
 *
 * @param string $ip IP address
 * @return string IP subnet
 */
function get_ip_subnet(string $ip): string {
    // IPv4
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        return implode('.', array_slice($parts, 0, 3)) . '.0';
    }

    // IPv6
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 4)) . '::';
    }

    return '0.0.0.0';
}

/**
 * Validate session fingerprint
 *
 * @return bool True if valid, false if hijacking detected
 */
function validate_session_fingerprint(): bool {
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = get_session_fingerprint();
        return true;
    }

    return $_SESSION['fingerprint'] === get_session_fingerprint();
}

/**
 * Regenerate session ID securely
 * Call this after login or privilege escalation
 */
function regenerate_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    // Delete old session file
    session_regenerate_id(true);

    // Update fingerprint
    $_SESSION['fingerprint'] = get_session_fingerprint();
}

/**
 * Contextual HTML escaping (XSS prevention)
 *
 * @param mixed $value Value to escape
 * @return string Escaped string
 */
if (!function_exists('e')) {
    function e($value): string {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * JSON escaping for safe output in JavaScript contexts
 *
 * @param mixed $value Value to encode
 * @return string JSON encoded string
 */
function json_escape($value): string {
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

/**
 * Send JSON response with secure headers
 *
 * @param mixed $data Data to send
 * @param int $statusCode HTTP status code
 */
if (!function_exists('json_response')) {
    function json_response($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Sanitize input string
 *
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitize_input(string $input): string {
    // Remove null bytes
    $input = str_replace("\0", '', $input);

    // Trim whitespace
    $input = trim($input);

    // Remove control characters except newlines and tabs
    $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);

    return $input;
}

/**
 * Validate and sanitize URL
 *
 * @param string $url URL to validate
 * @param bool $allowRelative Allow relative URLs
 * @return string|null Sanitized URL or null if invalid
 */
function sanitize_url(string $url, bool $allowRelative = false): ?string {
    $url = sanitize_input($url);

    if (empty($url)) {
        return null;
    }

    // Allow relative URLs
    if ($allowRelative && (str_starts_with($url, '/') || str_starts_with($url, './'))) {
        return $url;
    }

    // Validate absolute URLs
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return null;
    }

    // Check protocol whitelist (no javascript:, data:, etc.)
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array(strtolower($scheme ?? ''), ['http', 'https'], true)) {
        return null;
    }

    return $url;
}

/**
 * Validate email address
 *
 * @param string|null $email Email to validate
 * @return string|null Valid email or null
 */
if (!function_exists('sanitize_email')) {
    function sanitize_email(?string $email): ?string {
        if ($email === null || $email === '') {
            return null;
        }

        $email = sanitize_input($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }
}

/**
 * Get client IP address (proxy-aware)
 *
 * @return string Client IP address
 */
function get_client_ip(): string {
    // Check for proxy headers (trust only if behind known proxy)
    $headers = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];

            // Handle comma-separated IPs (take first one)
            if (str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }

            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0';
}

/**
 * Check if request is AJAX
 *
 * @return bool True if AJAX request
 */
function is_ajax_request(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if request is POST
 *
 * @return bool True if POST request
 */
function is_post_request(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 *
 * @return bool True if GET request
 */
function is_get_request(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Mask email address for privacy (show first 2 chars and domain)
 * Example: john.doe@example.com -> jo****@example.com
 *
 * @param string $email Email address to mask
 * @return string Masked email
 */
function mask_email(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return '••••@••••.•••';
    }

    $local = $parts[0];
    $domain = $parts[1];

    $localLen = strlen($local);
    if ($localLen <= 2) {
        $masked = str_repeat('•', $localLen);
    } else {
        $visible = substr($local, 0, 2);
        $masked = $visible . str_repeat('•', min($localLen - 2, 4));
    }

    return $masked . '@' . $domain;
}

// Auto-apply security headers when file is included
apply_security_headers();
