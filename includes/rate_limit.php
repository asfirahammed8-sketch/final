<?php
/**
 * Rate Limiting System
 *
 * Prevents brute-force attacks, spam, and API abuse
 * Uses sliding window algorithm with configurable limits
 */

/**
 * Rate limit storage (uses session for simplicity, can be upgraded to Redis/Memcached)
 */
class RateLimiter {
    private const STORAGE_KEY = 'rate_limits';

    /**
     * Check if request is allowed under rate limit
     *
     * @param string $key Unique identifier (e.g., 'login:192.168.1.1' or 'api:user_123')
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $windowSeconds Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public static function check(string $key, int $maxAttempts, int $windowSeconds): bool {
        $bucket = self::getBucket($key);
        $now = time();

        // Clean expired entries
        $bucket = array_filter($bucket, fn($timestamp) => ($now - $timestamp) < $windowSeconds);

        // Check if limit exceeded
        if (count($bucket) >= $maxAttempts) {
            self::saveBucket($key, $bucket);
            return false;
        }

        // Add current attempt
        $bucket[] = $now;
        self::saveBucket($key, $bucket);

        return true;
    }

    /**
     * Get remaining attempts
     *
     * @param string $key Unique identifier
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $windowSeconds Time window in seconds
     * @return int Remaining attempts
     */
    public static function remaining(string $key, int $maxAttempts, int $windowSeconds): int {
        $bucket = self::getBucket($key);
        $now = time();

        // Clean expired entries
        $bucket = array_filter($bucket, fn($timestamp) => ($now - $timestamp) < $windowSeconds);

        $used = count($bucket);
        return max(0, $maxAttempts - $used);
    }

    /**
     * Get seconds until rate limit resets
     *
     * @param string $key Unique identifier
     * @param int $windowSeconds Time window in seconds
     * @return int Seconds until reset (0 if not rate limited)
     */
    public static function retryAfter(string $key, int $windowSeconds): int {
        $bucket = self::getBucket($key);

        if (empty($bucket)) {
            return 0;
        }

        $oldest = min($bucket);
        $now = time();
        $elapsed = $now - $oldest;

        return max(0, $windowSeconds - $elapsed);
    }

    /**
     * Reset rate limit for a key
     *
     * @param string $key Unique identifier
     */
    public static function reset(string $key): void {
        self::saveBucket($key, []);
    }

    /**
     * Clear all rate limits (admin function)
     */
    public static function clearAll(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        unset($_SESSION[self::STORAGE_KEY]);
    }

    /**
     * Get bucket data
     *
     * @param string $key Unique identifier
     * @return array Array of timestamps
     */
    private static function getBucket(string $key): array {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[self::STORAGE_KEY])) {
            $_SESSION[self::STORAGE_KEY] = [];
        }

        return $_SESSION[self::STORAGE_KEY][$key] ?? [];
    }

    /**
     * Save bucket data
     *
     * @param string $key Unique identifier
     * @param array $bucket Array of timestamps
     */
    private static function saveBucket(string $key, array $bucket): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[self::STORAGE_KEY])) {
            $_SESSION[self::STORAGE_KEY] = [];
        }

        $_SESSION[self::STORAGE_KEY][$key] = array_values($bucket);
    }
}

/**
 * Rate limit login attempts
 *
 * @param string $identifier IP address or username
 * @param int $maxAttempts Maximum attempts (default: 5)
 * @param int $windowSeconds Time window in seconds (default: 900 = 15 minutes)
 * @return bool True if allowed, false if rate limited
 */
function rate_limit_login(string $identifier, int $maxAttempts = 5, int $windowSeconds = 900): bool {
    $key = 'login:' . hash('sha256', $identifier);
    return RateLimiter::check($key, $maxAttempts, $windowSeconds);
}

/**
 * Rate limit API requests
 *
 * @param string $identifier IP address or user ID
 * @param int $maxAttempts Maximum requests (default: 60)
 * @param int $windowSeconds Time window in seconds (default: 60 = 1 minute)
 * @return bool True if allowed, false if rate limited
 */
function rate_limit_api(string $identifier, int $maxAttempts = 60, int $windowSeconds = 60): bool {
    $key = 'api:' . hash('sha256', $identifier);
    return RateLimiter::check($key, $maxAttempts, $windowSeconds);
}

/**
 * Rate limit form submissions (contact, enquiry, etc.)
 *
 * @param string $identifier IP address or session ID
 * @param int $maxAttempts Maximum attempts (default: 3)
 * @param int $windowSeconds Time window in seconds (default: 300 = 5 minutes)
 * @return bool True if allowed, false if rate limited
 */
function rate_limit_form(string $identifier, int $maxAttempts = 3, int $windowSeconds = 300): bool {
    $key = 'form:' . hash('sha256', $identifier);
    return RateLimiter::check($key, $maxAttempts, $windowSeconds);
}

/**
 * Require rate limit check or respond with 429 Too Many Requests
 *
 * @param string $type Type of rate limit ('login', 'api', 'form', or custom)
 * @param string $identifier Unique identifier
 * @param int $maxAttempts Maximum attempts
 * @param int $windowSeconds Time window in seconds
 */
function rate_limit_require(string $type, string $identifier, int $maxAttempts, int $windowSeconds): void {
    $key = $type . ':' . hash('sha256', $identifier);

    if (!RateLimiter::check($key, $maxAttempts, $windowSeconds)) {
        $retryAfter = RateLimiter::retryAfter($key, $windowSeconds);

        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('X-RateLimit-Limit: ' . $maxAttempts);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . (time() + $retryAfter));

        if (defined('API_REQUEST') && API_REQUEST) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.',
                'retry_after' => $retryAfter
            ]);
        } else {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rate Limit Exceeded</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .error { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 500px; text-align: center; }
        h1 { color: #ed8936; margin: 0 0 16px; font-size: 24px; }
        p { color: #4a5568; margin: 0 0 24px; line-height: 1.6; }
        .countdown { font-size: 48px; font-weight: 700; color: #ed8936; margin: 20px 0; }
        .note { background: #fef5e7; border-left: 4px solid #ed8936; padding: 12px; margin-top: 20px; text-align: left; color: #744210; }
    </style>
</head>
<body>
    <div class="error">
        <h1>⏱️ Rate Limit Exceeded</h1>
        <p>You have made too many requests. Please wait before trying again.</p>
        <div class="countdown" id="countdown">' . $retryAfter . 's</div>
        <div class="note">
            <strong>Why am I seeing this?</strong><br>
            This protection prevents automated abuse and ensures fair access for all users.
        </div>
    </div>
    <script>
        let seconds = ' . $retryAfter . ';
        const countdown = document.getElementById("countdown");
        const timer = setInterval(() => {
            seconds--;
            countdown.textContent = seconds + "s";
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
        }, 1000);
    </script>
</body>
</html>';
        }
        exit;
    }

    // Add rate limit headers for monitoring
    $remaining = RateLimiter::remaining($key, $maxAttempts, $windowSeconds);
    header('X-RateLimit-Limit: ' . $maxAttempts);
    header('X-RateLimit-Remaining: ' . $remaining);
    header('X-RateLimit-Reset: ' . (time() + $windowSeconds));
}

/**
 * Honeypot validation (anti-spam)
 * Use this to detect bots filling hidden form fields
 *
 * @param string $fieldName Name of honeypot field
 * @return bool True if valid (field empty), false if spam detected
 */
function honeypot_validate(string $fieldName = 'website'): bool {
    // Honeypot field should be empty for real users
    // Bots typically fill all fields automatically
    return empty($_POST[$fieldName]);
}

/**
 * Require honeypot validation or block request
 *
 * @param string $fieldName Name of honeypot field
 */
function honeypot_require(string $fieldName = 'website'): void {
    if (!honeypot_validate($fieldName)) {
        // Silently reject (don't reveal anti-spam mechanism)
        http_response_code(400);

        if (defined('API_REQUEST') && API_REQUEST) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'VALIDATION_FAILED',
                'message' => 'Invalid form submission.'
            ]);
        } else {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Validation Error</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .error { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 500px; text-align: center; }
        h1 { color: #e53e3e; margin: 0 0 16px; font-size: 24px; }
        p { color: #4a5568; margin: 0 0 24px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="error">
        <h1>❌ Validation Error</h1>
        <p>Your form submission could not be processed. Please go back and try again.</p>
    </div>
</body>
</html>';
        }
        exit;
    }
}

/**
 * Generate honeypot HTML field
 * Add this to forms (hidden with CSS, not display:none)
 *
 * @param string $fieldName Name of honeypot field
 * @return string HTML input field
 */
function honeypot_field(string $fieldName = 'website'): string {
    // Use realistic field name that bots might fill
    // Style with position:absolute;left:-9999px to hide from bots
    return '<div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
        <label for="' . e($fieldName) . '">Leave this field empty</label>
        <input type="text" name="' . e($fieldName) . '" id="' . e($fieldName) . '" value="" tabindex="-1" autocomplete="off">
    </div>';
}
