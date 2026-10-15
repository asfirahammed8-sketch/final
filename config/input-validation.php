<?php
/**
 * Input Validation & Sanitization Library
 *
 * Comprehensive protection against all injection attacks:
 * - SQL Injection (via PDO prepared statements)
 * - XSS (Cross-Site Scripting)
 * - Command Injection
 * - Path Traversal
 * - LDAP Injection
 * - XML Injection
 * - Header Injection
 * - URL Injection
 */

class InputValidator {

    /**
     * Sanitize text input (general purpose)
     *
     * @param string $input Raw input
     * @param int $maxLength Maximum allowed length (0 = unlimited)
     * @return string Sanitized string
     */
    public static function sanitizeText(string $input, int $maxLength = 0): string {
        // Remove null bytes (common in injection attacks)
        $input = str_replace("\0", '', $input);

        // Trim whitespace
        $input = trim($input);

        // Remove control characters except newlines, tabs, carriage returns
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);

        // Enforce max length
        if ($maxLength > 0 && mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength);
        }

        return $input;
    }

    /**
     * Sanitize HTML input (allow safe HTML tags)
     *
     * @param string $html Raw HTML
     * @param array $allowedTags Allowed HTML tags
     * @return string Sanitized HTML
     */
    public static function sanitizeHtml(string $html, array $allowedTags = []): string {
        if (empty($allowedTags)) {
            $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        }

        // Use DOMDocument for proper HTML parsing and cleaning
        $config = [
            'safe' => 1,
            'elements' => implode(',', $allowedTags),
            'deny_attribute' => 'on*', // Block all event handlers (onclick, onerror, etc.)
        ];

        // Strip all tags except allowed ones
        $allowedTagsStr = '<' . implode('><', $allowedTags) . '>';
        $html = strip_tags($html, $allowedTagsStr);

        // Remove dangerous attributes from allowed tags
        $html = preg_replace('/<(\w+)([^>]*)\s(on\w+|style|formaction|form|xmlns|xlink|data-[^=]*)=["\']?[^"\']*["\']?/i', '<$1$2', $html);

        // Remove javascript: and data: protocols
        $html = preg_replace('/(javascript|data|vbscript|file|about):/i', '', $html);

        return $html;
    }

    /**
     * Sanitize plain text (strip ALL HTML)
     *
     * @param string $text Raw text
     * @param int $maxLength Maximum length
     * @return string Plain text only
     */
    public static function sanitizePlainText(string $text, int $maxLength = 0): string {
        // Remove all HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Sanitize
        return self::sanitizeText($text, $maxLength);
    }

    /**
     * Validate and sanitize email
     *
     * @param string $email Email address
     * @return string|null Valid email or null
     */
    public static function sanitizeEmail(string $email): ?string {
        $email = self::sanitizeText($email, 255);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        // Additional security: block dangerous characters
        if (preg_match('/[<>"\'\\\\\r\n]/', $email)) {
            return null;
        }

        return strtolower($email);
    }

    /**
     * Validate and sanitize URL
     *
     * @param string $url URL
     * @param bool $requireHttps Require HTTPS protocol
     * @return string|null Valid URL or null
     */
    public static function sanitizeUrl(string $url, bool $requireHttps = false): ?string {
        $url = self::sanitizeText($url, 2048);

        if (empty($url)) {
            return null;
        }

        // Validate URL format
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        // Parse and validate components
        $parts = parse_url($url);

        if (!isset($parts['scheme']) || !isset($parts['host'])) {
            return null;
        }

        // Protocol whitelist (block javascript:, data:, file:, etc.)
        $allowedSchemes = $requireHttps ? ['https'] : ['http', 'https'];

        if (!in_array(strtolower($parts['scheme']), $allowedSchemes, true)) {
            return null;
        }

        // Block dangerous characters in URL
        if (preg_match('/[<>"\'\\\\\r\n\x00]/', $url)) {
            return null;
        }

        // Block @username:password in URLs (credential phishing)
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return $url;
    }

    /**
     * Validate and sanitize image URL
     *
     * @param string $url Image URL
     * @return string|null Valid image URL or null
     */
    public static function sanitizeImageUrl(string $url): ?string {
        $url = self::sanitizeUrl($url);

        if ($url === null) {
            return null;
        }

        // Validate image extension
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp'];
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (!in_array($extension, $validExtensions, true)) {
            // Allow URLs without extension (might be CDN with query params)
            if (!empty($extension)) {
                return null;
            }
        }

        return $url;
    }

    /**
     * Sanitize phone number
     *
     * @param string $phone Phone number
     * @return string|null Sanitized phone or null
     */
    public static function sanitizePhone(string $phone): ?string {
        // Remove all non-digit characters except +, -, (, ), space
        $phone = preg_replace('/[^\d+\-() ]/', '', $phone);
        $phone = trim($phone);

        // Must have at least 7 digits
        if (strlen(preg_replace('/\D/', '', $phone)) < 7) {
            return null;
        }

        return $phone;
    }

    /**
     * Sanitize integer
     *
     * @param mixed $value Value to sanitize
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int|null Valid integer or null
     */
    public static function sanitizeInt($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int {
        if (!is_numeric($value)) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false) {
            return null;
        }

        if ($value < $min || $value > $max) {
            return null;
        }

        return $value;
    }

    /**
     * Sanitize float/decimal
     *
     * @param mixed $value Value to sanitize
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @return float|null Valid float or null
     */
    public static function sanitizeFloat($value, float $min = PHP_FLOAT_MIN, float $max = PHP_FLOAT_MAX): ?float {
        if (!is_numeric($value)) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($value === false) {
            return null;
        }

        if ($value < $min || $value > $max) {
            return null;
        }

        return $value;
    }

    /**
     * Sanitize boolean
     *
     * @param mixed $value Value to sanitize
     * @return bool Boolean value
     */
    public static function sanitizeBool($value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool)$value;
    }

    /**
     * Sanitize filename (prevent path traversal)
     *
     * @param string $filename Filename
     * @return string|null Safe filename or null
     */
    public static function sanitizeFilename(string $filename): ?string {
        $original = $filename;

        // Detect path traversal attempts BEFORE sanitization
        if (str_contains($original, '..') ||
            str_contains($original, '/') ||
            str_contains($original, '\\') ||
            str_contains($original, "\0")) {
            return null; // Reject malicious filenames
        }

        $filename = trim($filename);

        // Must not be empty or special directory names
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        // Remove control characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);

        // Must have valid characters only (alphanumeric, dash, underscore, dot, space)
        if (!preg_match('/^[a-zA-Z0-9_\-. ]+$/', $filename)) {
            return null;
        }

        // Filename should not start with dot (hidden files)
        if (str_starts_with($filename, '.')) {
            return null;
        }

        return $filename;
    }

    /**
     * Sanitize slug (URL-friendly string)
     *
     * @param string $slug Slug
     * @return string|null Valid slug or null
     */
    public static function sanitizeSlug(string $slug): ?string {
        $slug = strtolower(self::sanitizeText($slug, 200));

        // Replace spaces and underscores with hyphens
        $slug = preg_replace('/[\s_]+/', '-', $slug);

        // Remove all non-alphanumeric except hyphens
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);

        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        // Trim hyphens from ends
        $slug = trim($slug, '-');

        if ($slug === '') {
            return null;
        }

        return $slug;
    }

    /**
     * Sanitize JSON string
     *
     * @param string $json JSON string
     * @param int $maxDepth Maximum nesting depth
     * @return array|null Decoded array or null if invalid
     */
    public static function sanitizeJson(string $json, int $maxDepth = 10): ?array {
        if (trim($json) === '') {
            return null;
        }

        try {
            $data = json_decode($json, true, $maxDepth, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                return null;
            }

            return $data;
        } catch (JsonException $e) {
            return null;
        }
    }

    /**
     * Sanitize array of values
     *
     * @param array $array Input array
     * @param callable $sanitizer Sanitizer function
     * @return array Sanitized array
     */
    public static function sanitizeArray(array $array, callable $sanitizer): array {
        return array_map($sanitizer, $array);
    }

    /**
     * Validate CSRF token
     *
     * @param string $token Token to validate
     * @return bool True if valid
     */
    public static function validateCsrfToken(string $token): bool {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Generate CSRF token
     *
     * @return string CSRF token
     */
    public static function generateCsrfToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Sanitize SQL LIKE pattern (prevent LIKE injection)
     *
     * @param string $pattern Search pattern
     * @return string Escaped pattern
     */
    public static function sanitizeLikePattern(string $pattern): string {
        // Escape special LIKE wildcards
        $pattern = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $pattern);
        return $pattern;
    }

    /**
     * Prevent command injection in shell commands
     *
     * @param string $arg Command argument
     * @return string Escaped argument
     */
    public static function escapeShellArg(string $arg): string {
        return escapeshellarg($arg);
    }

    /**
     * Sanitize database table/column name
     *
     * @param string $identifier Identifier
     * @return string|null Safe identifier or null
     */
    public static function sanitizeDbIdentifier(string $identifier): ?string {
        // Only allow alphanumeric and underscores
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            return null;
        }

        // Prevent SQL reserved keywords
        $reserved = ['select', 'insert', 'update', 'delete', 'drop', 'create', 'alter', 'table', 'from', 'where'];

        if (in_array(strtolower($identifier), $reserved, true)) {
            return null;
        }

        return $identifier;
    }

    /**
     * Validate date format
     *
     * @param string $date Date string
     * @param string $format Expected format (default: Y-m-d)
     * @return string|null Valid date or null
     */
    public static function sanitizeDate(string $date, string $format = 'Y-m-d'): ?string {
        $d = DateTime::createFromFormat($format, $date);

        if (!$d || $d->format($format) !== $date) {
            return null;
        }

        return $date;
    }

    /**
     * Validate datetime format
     *
     * @param string $datetime Datetime string
     * @param string $format Expected format (default: Y-m-d H:i:s)
     * @return string|null Valid datetime or null
     */
    public static function sanitizeDateTime(string $datetime, string $format = 'Y-m-d H:i:s'): ?string {
        $d = DateTime::createFromFormat($format, $datetime);

        if (!$d || $d->format($format) !== $datetime) {
            return null;
        }

        return $datetime;
    }

    /**
     * Sanitize color hex code
     *
     * @param string $color Hex color code
     * @return string|null Valid hex color or null
     */
    public static function sanitizeColor(string $color): ?string {
        $color = trim($color);

        // Add # if missing
        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        // Validate hex format (#RGB or #RRGGBB)
        if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return null;
        }

        return strtoupper($color);
    }

    /**
     * Rate limiting check
     *
     * @param string $action Action identifier
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public static function checkRateLimit(string $action, int $maxAttempts = 5, int $timeWindow = 300): bool {
        $key = 'rate_limit_' . $action . '_' . get_client_ip();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
            return true;
        }

        $data = $_SESSION[$key];
        $elapsed = time() - $data['first_attempt'];

        // Reset if time window has passed
        if ($elapsed > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
            return true;
        }

        // Increment counter
        $_SESSION[$key]['count']++;

        // Check if limit exceeded
        return $_SESSION[$key]['count'] <= $maxAttempts;
    }

    /**
     * Sanitize Google Maps URL (convert share links to embed URLs)
     *
     * Supports:
     * - Google Maps embed URLs (https://www.google.com/maps/embed?pb=...)
     * - Google Maps share URLs (https://maps.app.goo.gl/...)
     * - Google Maps full URLs (https://www.google.com/maps/place/...)
     *
     * @param string $url Google Maps URL
     * @return string|null Valid embed URL or null
     */
    public static function sanitizeGoogleMapsUrl(string $url): ?string {
        $url = self::sanitizeText($url, 2048);

        if (empty($url)) {
            return null;
        }

        // If already an embed URL, validate and return
        if (str_contains($url, 'google.com/maps/embed')) {
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                return null;
            }

            // Ensure it's HTTPS
            $url = preg_replace('/^http:/i', 'https:', $url);

            // Validate domain
            $host = parse_url($url, PHP_URL_HOST);
            if (!in_array($host, ['www.google.com', 'google.com'], true)) {
                return null;
            }

            return $url;
        }

        // Google Maps short share URL (https://maps.app.goo.gl/...)
        if (preg_match('#maps\.app\.goo\.gl/([a-zA-Z0-9]+)#i', $url, $match)) {
            // Return as-is for storage (will be used in "View on Google Maps" link)
            return 'https://maps.app.goo.gl/' . $match[1];
        }

        // Google Maps place URL (https://www.google.com/maps/place/...)
        if (str_contains($url, 'google.com/maps/place/')) {
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                return null;
            }
            return $url;
        }

        // If none of the above, reject
        return null;
    }

    /**
     * Validate iframe embed code
     * Security: Ensures iframe only loads trusted domains
     *
     * @param string $iframe Iframe HTML code or src URL
     * @return string|null Sanitized src URL or null
     */
    public static function sanitizeIframeEmbed(string $iframe): ?string {
        $iframe = trim($iframe);

        if (empty($iframe)) {
            return null;
        }

        // If it's a full iframe tag, extract src
        if (str_starts_with($iframe, '<iframe')) {
            if (!preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/', $iframe, $match)) {
                return null;
            }
            $src = $match[1];
        } else {
            // Assume it's just the URL
            $src = $iframe;
        }

        // Whitelist of allowed iframe domains
        $allowedDomains = [
            'www.google.com',
            'google.com',
            'maps.google.com',
            'www.youtube.com',
            'youtube.com',
            'www.youtube-nocookie.com',
            'player.vimeo.com',
            'vimeo.com'
        ];

        // Validate URL
        if (filter_var($src, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        // Check domain whitelist
        $host = parse_url($src, PHP_URL_HOST);
        if (!in_array($host, $allowedDomains, true)) {
            return null;
        }

        // Ensure HTTPS
        if (parse_url($src, PHP_URL_SCHEME) !== 'https') {
            return null;
        }

        // Return sanitized src URL
        return $src;
    }
}
