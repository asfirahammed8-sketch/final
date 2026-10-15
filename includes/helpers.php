<?php
/**
 * Global Utility and Security Helpers
 * Creative IT Institute - DreamIT Platform
 */

// Load security functions first
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/input-validation.php';

if (session_status() === PHP_SESSION_NONE) {
    // Session name hardened to prevent fingerprinting the default 'PHPSESSID'
    session_name('DREAMIT_SESSION');

    // Use a 30-day max cookie lifetime so remembered sessions survive browser restarts,
    // but the server-side idle timeout (SESSION_IDLE_TIMEOUT, 30 min) provides real expiry.
    $sessionLifetime = (int)(getenv('SESSION_LIFETIME') ?: 2592000); // 30 days default
    ini_set('session.gc_maxlifetime', $sessionLifetime);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['SERVER_PORT'] ?? '') == 443);

    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',  // 'Strict' would break OAuth/social redirects; Lax is sufficient
    ]);
    session_start();
}

/**
 * Contextual HTML escaping (XSS prevention)
 * Note: This function is defined in config/security.php
 * Kept here as comment for reference
 */
// function e($value): string - See config/security.php

/**
 * Generate or retrieve CSRF token for the active session
 * Note: Primary implementation is in includes/csrf.php
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Render a hidden CSRF token input field
 * Note: Primary implementation is in includes/csrf.php
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

/**
 * Verify CSRF token from POST body or custom header
 * Note: Primary implementation is in includes/csrf.php
 */
if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token = null): bool {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Standardized JSON API success response
 */
function api_success($data = [], string $message = "Operation successful", int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'data'    => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Standardized JSON API error response
 */
function api_error(string $code, string $message, array $fields = [], int $statusCode = 400): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'error'   => [
            'code'    => $code,
            'message' => $message,
            'fields'  => $fields
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Backward-compatible json_response wrapper
 * Note: Primary implementation is in config/security.php
 */
if (!function_exists('json_response')) {
    function json_response($data, int $statusCode = 200): void {
        if (is_array($data) && isset($data['success'])) {
            if ($data['success']) {
                api_success($data['data'] ?? [], $data['message'] ?? 'Operation successful', $statusCode);
            } else {
                api_error($data['code'] ?? 'ERROR', $data['message'] ?? ($data['error'] ?? 'An error occurred'), $data['fields'] ?? [], $statusCode);
            }
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

/**
 * Redirect with optional exit
 */
function redirect(string $url, int $statusCode = 302): void {
    header("Location: " . $url, true, $statusCode);
    exit;
}

/**
 * Input Validators
 */
function validate_email(?string $email): bool {
    return !empty($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

function validate_phone(?string $phone): bool {
    if (empty($phone)) return false;
    $clean = preg_replace('/[^0-9+]/', '', trim($phone));
    return strlen($clean) >= 7 && strlen($clean) <= 17;
}

function validate_url(?string $url): bool {
    return !empty($url) && filter_var(trim($url), FILTER_VALIDATE_URL) !== false;
}

function validate_required(?string $val, int $minLength = 1): bool {
    return $val !== null && mb_strlen(trim($val)) >= $minLength;
}

function validate_slug(?string $slug): bool {
    return !empty($slug) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', trim($slug));
}

/**
 * Sanitize clean string input
 */
function sanitize_text(?string $str): string {
    if ($str === null) return '';
    return trim(strip_tags($str));
}

/**
 * Sanitize valid email
 * Note: This function is defined in config/security.php
 * Kept here as comment for reference
 */
// function sanitize_email(?string $email): ?string - See config/security.php

/**
 * Base URL detection
 */
/**
 * Base URL detection (auto-detects HTTPS, domain/host, and subfolder path)
 */
/**
 * Generate absolute URL from relative path
 * Now fully controlled by APP_URL and BASE_PATH in .env file
 *
 * @param string $path Relative path (e.g., 'about/', 'courses/')
 * @return string Full absolute URL
 */
function base_url(string $path = ''): string {
    // Priority 1: Use APP_URL from .env if set (best for production)
    $appUrl = getenv('APP_URL');
    if (!empty($appUrl) && filter_var($appUrl, FILTER_VALIDATE_URL)) {
        $parsedUrl = parse_url($appUrl);
        $protocol = ($parsedUrl['scheme'] ?? 'http') . '://';
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $root = $parsedUrl['path'] ?? '';
        $root = rtrim($root, '/');

        $cleanPath = ltrim($path, '/');
        return $protocol . $host . $port . ($root ? $root : '') . ($cleanPath ? '/' . $cleanPath : '');
    }

    // Priority 2: Use BASE_PATH from .env (simple subfolder setup)
    $basePath = getenv('BASE_PATH');
    if ($basePath !== false) {
        $root = rtrim($basePath, '/');
    } else {
        // Priority 3: Auto-detect from current script path (fallback)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $root = preg_replace('#/(admin|api|frontend.*|pages.*)$#', '', $scriptDir);
        $root = rtrim($root, '/\\');
    }

    // Detect protocol
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on')
        || (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false)
        || (strtolower($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '') === 'on');
    $protocol = $isHttps ? "https://" : "http://";

    // Detect host
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    if (strpos($host, ',') !== false) {
        $host = trim(explode(',', $host)[0]);
    }

    $cleanPath = ltrim($path, '/');
    return $protocol . $host . ($root ? $root : '') . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * Returns the auto-detected canonical base URL.
/**
 * Dynamic Website Brand Name
 */
function site_brand(): string {
    $gen = get_setting('general', []);
    if (!empty($gen['site_name'])) {
        return (string)$gen['site_name'];
    }
    $flatName = get_setting('site_name', null);
    if (!empty($flatName)) {
        return (string)$flatName;
    }
    if (!empty($gen['site_title']) && $gen['site_title'] !== 'this is tag line') {
        return (string)$gen['site_title'];
    }
    return 'Creative IT Institute';
}

/**
 * Dynamic Website Tagline / Slogan
 */
function site_tagline(): string {
    $gen = get_setting('general', []);
    if (!empty($gen['site_tagline'])) {
        return (string)$gen['site_tagline'];
    }
    $flatTag = get_setting('site_tagline', null);
    if (!empty($flatTag)) {
        return (string)$flatTag;
    }
    if (!empty($gen['site_title'])) {
        return (string)$gen['site_title'];
    }
    return 'Professional IT Courses in Bangladesh';
}

/**
 * Dynamic Canonical Base URL
 * Reads live custom domain from admin panel site_settings first.
 * Automatically detects the current live host, protocol, and subfolder.
 * When deployed from localhost to a live server, seamlessly auto-upgrades the database.
 */
function get_canonical_base(): string {
    $currentHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));
    if (strpos($currentHost, ',') !== false) {
        $currentHost = trim(explode(',', $currentHost)[0]);
    }
    $isCurrentLocal = ($currentHost === 'localhost' || str_starts_with($currentHost, '127.0.0.1') || str_starts_with($currentHost, '192.168.') || str_ends_with($currentHost, '.test') || str_ends_with($currentHost, '.local'));

    // 1. Check if user configured a custom canonical base URL in site settings (general group)
    $general = get_setting('general', []);
    $customCanon = !empty($general['canonical_url']) ? $general['canonical_url'] : get_setting('canonical_url', null);

    if (!empty($customCanon) && filter_var($customCanon, FILTER_VALIDATE_URL)) {
        $parsedCanonHost = parse_url($customCanon, PHP_URL_HOST);
        $canonIsLocal = ($parsedCanonHost === 'localhost' || $parsedCanonHost === '127.0.0.1');

        // If currently running live on a real web host, but database had localhost from development, disregard localhost
        if (!(!$isCurrentLocal && $canonIsLocal && $currentHost !== '')) {
            return rtrim($customCanon, '/');
        }
    }

    // 2. Check if environment constant CANONICAL_URL was explicitly set
    if (defined('CANONICAL_URL') && trim(CANONICAL_URL) !== '' && filter_var(CANONICAL_URL, FILTER_VALIDATE_URL)) {
        $parsedEnvHost = parse_url(CANONICAL_URL, PHP_URL_HOST);
        $envIsLocal = ($parsedEnvHost === 'localhost' || $parsedEnvHost === '127.0.0.1');
        if (!(!$isCurrentLocal && $envIsLocal && $currentHost !== '')) {
            return rtrim(CANONICAL_URL, '/');
        }
    }

    // 3. True dynamic auto-detection from current server and protocol
    $detectedBase = rtrim(base_url(), '/');

    // Auto-Upgrade on First Live Visit:
    // If running on a live production domain and canonical_url was empty or localhost in DB,
    // automatically save this live domain to site_settings and trigger dynamic SEO database sync!
    static $liveMigrated = false;
    if (!$liveMigrated && !$isCurrentLocal && $currentHost !== '' && PHP_SAPI !== 'cli') {
        $liveMigrated = true;
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value, setting_group)
                VALUES ('canonical_url', :val, 'general')
                ON DUPLICATE KEY UPDATE setting_value = :val2, updated_at = NOW()
            ");
            $stmt->execute([':val' => $detectedBase, ':val2' => $detectedBase]);

            $gen = get_setting('general', []);
            if (is_array($gen)) {
                $gen['canonical_url'] = $detectedBase;
                $genJson = json_encode($gen, JSON_UNESCAPED_UNICODE);
                $stmtGen = $db->prepare("
                    INSERT INTO site_settings (setting_key, setting_value, setting_group)
                    VALUES ('general', :val, 'general')
                    ON DUPLICATE KEY UPDATE setting_value = :val2, updated_at = NOW()
                ");
                $stmtGen->execute([':val' => $genJson, ':val2' => $genJson]);
            }
            if (function_exists('sync_dynamic_page_seo')) {
                sync_dynamic_page_seo($db);
            }
        } catch (Exception $e) {
            // Continue gracefully
        }
    }

    return $detectedBase;
}

/**
 * Canonical URL builder (auto-detects current active host and routing path)
 */
function canonical_url(?string $path = null): string {
    $canonicalBase = get_canonical_base();
    if ($path === null) {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        // Strip project subfolder if present in path
        $root = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        $root = preg_replace('#/(admin|api|frontend.*|pages.*)$#', '', $root);
        if ($root && str_starts_with($path, $root)) {
            $path = substr($path, strlen($root));
        }
        $path = ltrim($path, '/');
    }
    $clean = ltrim($path, '/');
    return $clean === '' ? $canonicalBase . '/' : $canonicalBase . '/' . $clean;
}

/**
 * Clean Course URL permalink
 */
function clean_course_url(string $slug, bool $canonical = false): string {
    $path = 'courses/' . rawurlencode($slug) . '/';
    return $canonical ? canonical_url($path) : base_url($path);
}

/**
 * Clean Department Category URL permalink
 */
function clean_dept_url(string $dept, bool $canonical = false): string {
    $deptMap = [
        'graphic'   => 'graphic-design',
        'web'       => 'web-development',
        'digital'   => 'digital-marketing',
        'animation' => '3d-animation',
        'diploma'   => 'diploma-courses',
        'cyber'     => 'cyber-security',
    ];
    $slug = $deptMap[$dept] ?? $dept;
    $path = 'courses/' . rawurlencode($slug) . '/';
    return $canonical ? canonical_url($path) : base_url($path);
}

/**
 * Flash message helpers
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}


/**
 * Fetch a site setting by key with cache-busting support
 * @param string $key Setting key (e.g., 'general', 'contact')
 * @param mixed $default Default value if not found
 * @param bool $refresh Force refresh from database
 * @return mixed
 */
function get_setting(string $key, $default = null, bool $refresh = false) {
    static $settingsCache = null;
    static $cacheVersion = null;

    // Check if cache version has changed (settings updated in admin)
    if ($settingsCache !== null && !$refresh) {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT MAX(updated_at) as last_update FROM site_settings LIMIT 1");
            $row = $stmt->fetch();
            $currentVersion = $row ? $row['last_update'] : null;

            // If version changed, force refresh
            if ($currentVersion !== $cacheVersion) {
                $refresh = true;
            }
        } catch (Exception $e) {
            // Ignore version check errors
        }
    }

    if ($refresh || $settingsCache === null) {
        $settingsCache = [];
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT setting_key, setting_value, setting_group, MAX(updated_at) as last_update FROM site_settings GROUP BY setting_key, setting_value, setting_group");
            while ($row = $stmt->fetch()) {
                $decoded = json_decode($row['setting_value'], true);
                $val = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $row['setting_value'];
                $settingsCache[$row['setting_key']] = $val;

                if (!empty($row['setting_group'])) {
                    $grp = $row['setting_group'];
                    if (!isset($settingsCache['__grp__' . $grp])) {
                        $settingsCache['__grp__' . $grp] = [];
                    }
                    $settingsCache['__grp__' . $grp][$row['setting_key']] = $val;
                    $cleanKey = preg_replace('/^' . preg_quote($grp, '/') . '_/', '', $row['setting_key']);
                    $settingsCache['__grp__' . $grp][$cleanKey] = $val;
                }

                if (!$cacheVersion || $row['last_update'] > $cacheVersion) {
                    $cacheVersion = $row['last_update'];
                }
            }
        } catch (Exception $e) {
            // DB might not yet be initialized
        }
    }
    if ($key === '') {
        return $settingsCache;
    }
    if (array_key_exists($key, $settingsCache)) {
        return $settingsCache[$key];
    }
    if (isset($settingsCache['__grp__' . $key])) {
        return $settingsCache['__grp__' . $key];
    }
    // Alias social_media -> social
    if ($key === 'social_media' && isset($settingsCache['__grp__social'])) {
        return $settingsCache['__grp__social'];
    }
    return $default;
}

/**
 * Format price in BDT currency format
 */
function format_bdt($amount): string {
    return number_format((float)$amount, 0, '.', ',');
}

/**
 * Strict Video URL Sanitizer & Validator
 * Validates against XSS, javascript: injection, data: URIs, etc.
 * Normalizes YouTube and Vimeo URLs into safe formats.
 */
function sanitize_video_url(?string $url): ?string {
    if ($url === null) return null;
    $trimmed = trim(strip_tags($url));
    if ($trimmed === '') return null;

    // Block dangerous schemes
    if (preg_match('/^(?:javascript|data|vbscript|file|about):/i', $trimmed)) {
        return null;
    }

    // Match YouTube URLs (watch?v=, embed/, shorts/, v/, youtu.be/)
    if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/|v/)|youtu\.be/)([a-zA-Z0-9_-]{11})#i', $trimmed, $m)) {
        return 'https://www.youtube.com/watch?v=' . $m[1];
    }

    // Match Vimeo URLs
    if (preg_match('#(?:vimeo\.com/(?:video/)?)([0-9]{6,12})#i', $trimmed, $m)) {
        return 'https://vimeo.com/' . $m[1];
    }

    // Direct video file link (must be http/https and have video extension)
    if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
        $scheme = parse_url($trimmed, PHP_URL_SCHEME);
        if (in_array(strtolower($scheme), ['http', 'https'], true)) {
            $path = parse_url($trimmed, PHP_URL_PATH);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v'], true)) {
                return $trimmed;
            }
        }
    }

    return null;
}

/**
 * Strict Image URL Sanitizer & Validator
 * Converts Google Drive / Dropbox view links into direct embeddable images.
 * Validates against XSS, javascript: injection, data: URIs, etc.
 */
function sanitize_image_url(?string $url): ?string {
    if ($url === null) return null;
    $trimmed = trim(strip_tags($url));
    if ($trimmed === '') return null;

    // Block dangerous schemes
    if (preg_match('/^(?:javascript|data|vbscript|file|about):/i', $trimmed)) {
        return null;
    }

    // Google Drive share link converter (drive.google.com/file/d/ID/... or open?id=ID or uc?id=ID)
    if (preg_match('#drive\.google\.com/(?:file/d/|open\?id=|uc\?id=)([a-zA-Z0-9_-]+)#i', $trimmed, $m)) {
        return 'https://drive.google.com/uc?export=view&id=' . $m[1];
    }

    // Dropbox link converter (dropbox.com/... ?dl=0 -> raw=1)
    if (preg_match('#dropbox\.com/(.+)$#i', $trimmed)) {
        $clean = preg_replace('/(\?|\&)dl=[01]/', '', $trimmed);
        $clean = preg_replace('/(\?|\&)raw=1/', '', $clean);
        $separator = str_contains($clean, '?') ? '&' : '?';
        return $clean . $separator . 'raw=1';
    }

    // Allow relative paths under uploads/
    if (preg_match('#^/?uploads/[a-zA-Z0-9_\-\./]+$#i', $trimmed)) {
        return base_url(ltrim($trimmed, '/'));
    }

    // Must be valid HTTP or HTTPS URL
    if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
        $scheme = parse_url($trimmed, PHP_URL_SCHEME);
        if (in_array(strtolower($scheme), ['http', 'https'], true)) {
            return $trimmed;
        }
    }

    return null;
}

/**
 * Safely converts a video URL into a privacy-enhanced embed URL
 */
function safe_video_embed_url(?string $url): ?string {
    if (!$url) return null;
    $clean = sanitize_video_url($url);
    if (!$clean) return null;

    if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([a-zA-Z0-9_-]{11})#i', $clean, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
    }
    if (preg_match('#vimeo\.com/([0-9]{6,12})#i', $clean, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
    }
    return $clean;
}

/**
 * Dynamic Page SEO Resolver with Auto-Detection and Best-Practice Fallbacks
 *
 * Automatically detects dynamic entities (courses, departments) and static pages.
 * If a page record does not exist in the `page_seo` database table, it auto-configures
 * and persists optimal metadata to the database automatically.
 */
function get_page_seo(string $pageSlug, array $runtimeContext = []): array {
    static $pageSeoCache = [];

    // Global site brand name and identity from settings
    $siteBrand = site_brand();
    $tagline = site_tagline();
    $generalSettings = get_setting('general', []);
    $defaultOgImage = !empty($generalSettings['logo_url']) ? sanitize_image_url($generalSettings['logo_url']) : base_url('frontend/assets/images/og-home.jpg');

    // 1. Dynamic Entity: Department Listing Page
    if ($pageSlug === 'department' && !empty($runtimeContext['department'])) {
        $dept = $runtimeContext['department'];
        $deptName = $dept['name'] ?? 'Department';
        $deptSlug = $dept['slug'] ?? '';
        $deptDesc = !empty($dept['description']) ? strip_tags($dept['description']) : '';
        $deptColor = $dept['color'] ?? '#c92127';

        $title = "{$deptName} Courses in Bangladesh | {$siteBrand}";
        $desc = $deptDesc !== ''
            ? $deptDesc
            : "Explore professional {$deptName} courses at {$siteBrand}. Industry-focused curriculum, hands-on training, expert mentors, and lifetime career support in Bangladesh.";
        $canonical = clean_dept_url($deptSlug, true);
        $ogImage = $defaultOgImage;

        // Auto-detect and auto-upgrade canonical_url in departments database table
        if (!empty($dept['id']) && (empty($dept['canonical_url']) || $dept['canonical_url'] !== $canonical)) {
            try {
                $db = Database::getConnection();
                $updateDeptCanon = $db->prepare("UPDATE departments SET canonical_url = :canon WHERE id = :id");
                $updateDeptCanon->execute([':canon' => $canonical, ':id' => (int)$dept['id']]);
            } catch (Exception $e) {
                // Ignore gracefully if read-only
            }
        }

        return [
            'page_slug'          => 'department',
            'page_name'          => $deptName,
            'title'              => $title,
            'meta_description'   => mb_substr($desc, 0, 160),
            'canonical_url'      => $canonical,
            'robots'             => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'og_title'           => $title,
            'og_description'     => mb_substr($desc, 0, 160),
            'og_image'           => $ogImage,
            'twitter_title'      => $title,
            'twitter_description'=> mb_substr($desc, 0, 160),
            'twitter_image'      => $ogImage,
            'is_custom'          => false,
            'source'             => 'department_entity',
            'department_color'   => $deptColor
        ];
    }

    // 2. Dynamic Entity: Course Detail Page
    if ($pageSlug === 'course-detail' && !empty($runtimeContext['course'])) {
        $course = $runtimeContext['course'];
        $courseTitle = $course['title'] ?? 'Course';
        $courseSlug  = $course['slug'] ?? '';
        $customTitle = !empty($course['seo_title']) ? trim($course['seo_title']) : '';
        $customDesc  = !empty($course['meta_description']) ? trim($course['meta_description']) : '';
        $customOgImg = !empty($course['thumbnail_url']) ? sanitize_image_url($course['thumbnail_url']) : '';

        $title = $customTitle !== '' ? $customTitle : "{$courseTitle} Course in Bangladesh | {$siteBrand}";
        $desc = $customDesc !== '' ? $customDesc : (!empty($course['overview']) ? strip_tags($course['overview']) : "Enroll in {$courseTitle} course at {$siteBrand}. Practical lab training, industry projects, and lifetime career support in Bangladesh.");
        $canonical = clean_course_url($courseSlug, true);
        $ogImage = $customOgImg ?: $defaultOgImage;

        // Auto-detect and auto-upgrade canonical_url in courses database table
        if (!empty($course['id']) && (empty($course['canonical_url']) || $course['canonical_url'] !== $canonical)) {
            try {
                $db = Database::getConnection();
                $updateCourseCanon = $db->prepare("UPDATE courses SET canonical_url = :canon WHERE id = :id");
                $updateCourseCanon->execute([':canon' => $canonical, ':id' => (int)$course['id']]);
            } catch (Exception $e) {
                // Ignore gracefully if read-only
            }
        }

        return [
            'page_slug'          => 'course-detail',
            'page_name'          => $courseTitle,
            'title'              => $title,
            'meta_description'   => $desc,
            'canonical_url'      => $canonical,
            'robots'             => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'og_title'           => $title,
            'og_description'     => $desc,
            'og_image'           => $ogImage,
            'twitter_title'      => $title,
            'twitter_description'=> $desc,
            'twitter_image'      => $ogImage,
            'is_custom'          => ($customTitle !== '' || $customDesc !== ''),
            'source'             => 'course_entity'
        ];
    }

    // 2. Static / Core Page from page_seo table
    if (!isset($pageSeoCache[$pageSlug])) {
        $row = null;
        $db = null;
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM page_seo WHERE page_slug = :slug LIMIT 1");
            $stmt->execute([':slug' => $pageSlug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Auto-detect and auto-upgrade: if this page slug is missing from database, auto-insert it!
            if (!$row && $pageSlug !== 'course-detail') {
                $row = auto_detect_page_seo($pageSlug, $siteBrand, $runtimeContext, $db);
            }
        } catch (Exception $e) {
            // DB might not yet be initialized
        }
        $pageSeoCache[$pageSlug] = $row ?: [];
    }

    $data = $pageSeoCache[$pageSlug];

    // Compute automatic canonical URL based on slug
    $autoCanonical = canonical_url($pageSlug === 'home' ? '/' : "{$pageSlug}/");
    if (!empty($runtimeContext['canonical_url'])) {
        $autoCanonical = $runtimeContext['canonical_url'];
    }

    // Default title fallback
    $defaultTitle = !empty($runtimeContext['title']) ? $runtimeContext['title'] : "{$siteBrand} | {$tagline}";
    if ($pageSlug !== 'home' && empty($runtimeContext['title'])) {
        $pageName = $data['page_name'] ?? ucfirst(str_replace('-', ' ', $pageSlug));
        $defaultTitle = "{$pageName} | {$siteBrand}";
    }

    // Default description fallback
    $defaultDesc = !empty($runtimeContext['description'])
        ? $runtimeContext['description']
        : "Learn Graphic Design, Web Development, Digital Marketing, 3D Animation and more at {$siteBrand}. Explore professional IT courses with practical training and expert mentors.";

    $customTitle   = !empty($data['seo_title']) ? trim($data['seo_title']) : '';
    $customDesc    = !empty($data['meta_description']) ? trim($data['meta_description']) : '';
    $customCanon   = !empty($data['canonical_url']) ? trim($data['canonical_url']) : '';
    $customRobots  = !empty($data['robots']) ? trim($data['robots']) : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $customOgTitle = !empty($data['og_title']) ? trim($data['og_title']) : '';
    $customOgDesc  = !empty($data['og_description']) ? trim($data['og_description']) : '';
    $customOgImg   = !empty($data['og_image']) ? sanitize_image_url($data['og_image']) : '';
    $customTwTitle = !empty($data['twitter_title']) ? trim($data['twitter_title']) : '';
    $customTwDesc  = !empty($data['twitter_description']) ? trim($data['twitter_description']) : '';
    $customTwImg   = !empty($data['twitter_image']) ? sanitize_image_url($data['twitter_image']) : '';

    // Resolved title and description
    $resolvedTitle = $customTitle !== '' ? $customTitle : $defaultTitle;
    $resolvedDesc  = $customDesc !== '' ? $customDesc : $defaultDesc;

    // Strict canonical safety: must be valid absolute URL if custom, else auto
    $resolvedCanon = $autoCanonical;
    if ($customCanon !== '' && filter_var($customCanon, FILTER_VALIDATE_URL) && str_starts_with($customCanon, 'http')) {
        $resolvedCanon = $customCanon;
    }

    // Auto-detect and auto-upgrade canonical_url in page_seo table if missing or updated
    if ($pageSlug !== 'course-detail' && !empty($autoCanonical) && ($customCanon === '' || $customCanon !== $autoCanonical)) {
        try {
            if (!$db) $db = Database::getConnection();
            $stmtUpdate = $db->prepare("UPDATE page_seo SET canonical_url = :canon, updated_at = NOW() WHERE page_slug = :slug");
            $stmtUpdate->execute([':canon' => $autoCanonical, ':slug' => $pageSlug]);
            $resolvedCanon = $autoCanonical;
            $data['canonical_url'] = $autoCanonical;
        } catch (Exception $e) {
            // Silently ignore if DB read-only
        }
    }

    // Smart OG/Twitter inheritance: if blank, inherit from SEO title / description / image
    $resolvedOgTitle = $customOgTitle !== '' ? $customOgTitle : $resolvedTitle;
    $resolvedOgDesc  = $customOgDesc !== '' ? $customOgDesc : $resolvedDesc;
    $resolvedOgImg   = $customOgImg ?: (!empty($runtimeContext['og_image']) ? $runtimeContext['og_image'] : $defaultOgImage);

    $resolvedTwTitle = $customTwTitle !== '' ? $customTwTitle : $resolvedOgTitle;
    $resolvedTwDesc  = $customTwDesc !== '' ? $customTwDesc : $resolvedOgDesc;
    $resolvedTwImg   = $customTwImg ?: $resolvedOgImg;

    return [
        'page_slug'           => $pageSlug,
        'page_name'           => $data['page_name'] ?? ucfirst($pageSlug),
        'title'               => $resolvedTitle,
        'meta_description'    => $resolvedDesc,
        'canonical_url'       => $resolvedCanon,
        'robots'              => $customRobots,
        'og_title'            => $resolvedOgTitle,
        'og_description'      => $resolvedOgDesc,
        'og_image'            => $resolvedOgImg,
        'twitter_title'       => $resolvedTwTitle,
        'twitter_description' => $resolvedTwDesc,
        'twitter_image'       => $resolvedTwImg,
        'raw_seo_title'       => $customTitle,
        'raw_meta_description'=> $customDesc,
        'raw_canonical_url'   => $customCanon,
        'is_custom'           => ($customTitle !== '' || $customDesc !== ''),
        'source'              => 'page_seo'
    ];
}

/**
 * Auto-detect and register a newly discovered page into the page_seo database table
 */
function auto_detect_page_seo(string $pageSlug, string $siteBrand, array $runtimeContext = [], ?PDO $db = null): array {
    if (!$db) {
        try {
            $db = Database::getConnection();
        } catch (Exception $e) {
            return [];
        }
    }

    $pageName = !empty($runtimeContext['title']) ? $runtimeContext['title'] : ucfirst(str_replace('-', ' ', $pageSlug));
    $canonical = !empty($runtimeContext['canonical_url']) ? $runtimeContext['canonical_url'] : canonical_url($pageSlug === 'home' ? '/' : "{$pageSlug}/");
    $robots = ($pageSlug === '404') ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    $seoTitle = "{$pageName} | {$siteBrand}";
    if ($pageSlug === 'home') {
        $tagline = site_tagline();
        $seoTitle = "{$siteBrand} | {$tagline}";
    }

    $metaDesc = !empty($runtimeContext['description'])
        ? $runtimeContext['description']
        : "Discover {$pageName} at {$siteBrand}. Practical professional IT training, industry projects, and career counseling in Bangladesh.";

    $ogImage = base_url('frontend/assets/images/og-home.jpg');

    $record = [
        'page_slug'           => $pageSlug,
        'page_name'           => $pageName,
        'seo_title'           => $seoTitle,
        'meta_description'    => $metaDesc,
        'canonical_url'       => $canonical,
        'robots'              => $robots,
        'og_title'            => $seoTitle,
        'og_description'      => $metaDesc,
        'og_image'            => $ogImage,
        'twitter_title'       => $seoTitle,
        'twitter_description' => $metaDesc,
        'twitter_image'       => $ogImage,
        'updated_by'          => 'auto_detector'
    ];

    try {
        $stmt = $db->prepare("
            INSERT INTO page_seo (page_slug, page_name, seo_title, meta_description, canonical_url, robots, og_title, og_description, og_image, twitter_title, twitter_description, twitter_image, updated_by)
            VALUES (:page_slug, :page_name, :seo_title, :meta_description, :canonical_url, :robots, :og_title, :og_description, :og_image, :twitter_title, :twitter_description, :twitter_image, :updated_by)
            ON DUPLICATE KEY UPDATE
                canonical_url = VALUES(canonical_url),
                updated_at = NOW()
        ");
        $stmt->execute($record);
    } catch (Exception $e) {
        // Continue gracefully even if write fails
    }

    return $record;
}

/**
 * Auto-synchronize and upgrade the entire page_seo database table
 *
 * Triggered automatically when Admin updates Site Settings (Brand, Title, Logo, Contact).
 * Updates existing records and populates any missing core pages with optimal SEO best practices.
 */
function sync_dynamic_page_seo(?PDO $db = null): array {
    if (!$db) {
        try {
            $db = Database::getConnection();
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Refresh settings cache to guarantee newest brand and canonical values
    get_setting('', null, true);

    $siteBrand = site_brand();
    $tagline = site_tagline();
    $generalSettings = get_setting('general', []);
    $ogImg = !empty($generalSettings['logo_url']) ? sanitize_image_url($generalSettings['logo_url']) : base_url('frontend/assets/images/og-home.jpg');

    $corePages = [
        'home' => [
            'name'  => 'Home',
            'title' => "{$siteBrand} | {$tagline}",
            'desc'  => "Learn Graphic Design, Web Development, Digital Marketing, 3D Animation and more at {$siteBrand}. Explore professional IT courses with practical training and expert mentors.",
            'canon' => canonical_url('/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        'courses' => [
            'name'  => 'Courses',
            'title' => "All Professional IT Courses | {$siteBrand}",
            'desc'  => "Browse career-focused IT courses in Graphic Design, Web Development, Digital Marketing, 3D Animation, and Cyber Security at {$siteBrand}.",
            'canon' => canonical_url('courses/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        'about' => [
            'name'  => 'About Us',
            'title' => "About {$siteBrand} | Leading IT Training in Bangladesh",
            'desc'  => "Learn about {$siteBrand} — empowering Bangladesh's youth with world-class IT training, ISO 9001:2015 certified curriculum, and lifetime career support.",
            'canon' => canonical_url('about/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        'contact' => [
            'name'  => 'Contact Us',
            'title' => "Contact {$siteBrand} | Campus Admissions & Counseling",
            'desc'  => "Get in touch with {$siteBrand}. Contact our academic counselors, visit our campus, or book a free course consultation.",
            'canon' => canonical_url('contact/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        'faq' => [
            'name'  => 'FAQ',
            'title' => "Frequently Asked Questions | {$siteBrand}",
            'desc'  => "Find quick answers to common questions about {$siteBrand} courses, admission procedures, computer labs, batch schedules, certifications, and career support.",
            'canon' => canonical_url('faq/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        'gallery' => [
            'name'  => 'Gallery',
            'title' => "Campus Gallery & Lab Life | {$siteBrand}",
            'desc'  => "Explore {$siteBrand} campus life — high-tech computer labs, interactive student seminars, hackathons, and creative workstations.",
            'canon' => canonical_url('gallery/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        'success-stories' => [
            'name'  => 'Success Stories',
            'title' => "Graduate Success Stories & Reviews | {$siteBrand}",
            'desc'  => "Discover inspiring success stories from {$siteBrand} graduates who built thriving freelance careers and secured top IT positions worldwide.",
            'canon' => canonical_url('success-stories/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        'privacy' => [
            'name'  => 'Privacy Policy',
            'title' => "Privacy Policy | {$siteBrand}",
            'desc'  => "{$siteBrand} privacy policy — how we collect, use, store, and protect your personal data when enrolling in our IT courses or browsing our platform.",
            'canon' => canonical_url('privacy/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        'sitemap' => [
            'name'  => 'Sitemap',
            'title' => "Website Sitemap | {$siteBrand}",
            'desc'  => "Complete site index and navigation directory for {$siteBrand} website pages, courses, departments, and resources.",
            'canon' => canonical_url('sitemap/'),
            'robot' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        ],
        '404' => [
            'name'  => '404 Not Found',
            'title' => "Page Not Found | {$siteBrand}",
            'desc'  => "The requested page could not be found on {$siteBrand}.",
            'canon' => canonical_url('404.php'),
            'robot' => 'noindex, nofollow',
        ],
    ];

    $synced = 0;
    try {
        $stmt = $db->prepare("
            INSERT INTO page_seo (
                page_slug, page_name, seo_title, meta_description, canonical_url, robots,
                og_title, og_description, og_image, twitter_title, twitter_description, twitter_image, updated_by
            ) VALUES (
                :slug, :name, :seo_title, :meta_desc, :canon, :robot,
                :og_title, :og_desc, :og_image, :tw_title, :tw_desc, :tw_image, 'admin_auto_sync'
            )
            ON DUPLICATE KEY UPDATE
                canonical_url = VALUES(canonical_url),
                robots = VALUES(robots),
                seo_title = IF(updated_by = 'admin_manual', seo_title, VALUES(seo_title)),
                meta_description = IF(updated_by = 'admin_manual', meta_description, VALUES(meta_description)),
                og_title = IF(updated_by = 'admin_manual', og_title, VALUES(og_title)),
                og_description = IF(updated_by = 'admin_manual', og_description, VALUES(og_description)),
                og_image = IF(updated_by = 'admin_manual', og_image, VALUES(og_image)),
                twitter_title = IF(updated_by = 'admin_manual', twitter_title, VALUES(twitter_title)),
                twitter_description = IF(updated_by = 'admin_manual', twitter_description, VALUES(twitter_description)),
                twitter_image = IF(updated_by = 'admin_manual', twitter_image, VALUES(twitter_image)),
                updated_by = IF(updated_by = 'admin_manual', 'admin_manual', 'admin_auto_sync'),
                updated_at = NOW()
        ");

        foreach ($corePages as $slug => $def) {
            $stmt->execute([
                ':slug'       => $slug,
                ':name'       => $def['name'],
                ':seo_title'  => $def['title'],
                ':meta_desc'  => $def['desc'],
                ':canon'      => $def['canon'],
                ':robot'      => $def['robot'],
                ':og_title'   => $def['title'],
                ':og_desc'    => $def['desc'],
                ':og_image'   => $ogImg,
                ':tw_title'   => $def['title'],
                ':tw_desc'    => $def['desc'],
                ':tw_image'   => $ogImg,
            ]);
            $synced++;
        }

        // Auto-detect and auto-upgrade canonical_url for all courses in database
        try {
            $allCourses = $db->query("SELECT id, slug, canonical_url FROM courses")->fetchAll(PDO::FETCH_ASSOC);
            $updateCourseStmt = $db->prepare("UPDATE courses SET canonical_url = :canon WHERE id = :id");
            foreach ($allCourses as $c) {
                $expectedCanon = clean_course_url($c['slug'], true);
                if (empty($c['canonical_url']) || $c['canonical_url'] !== $expectedCanon) {
                    $updateCourseStmt->execute([':canon' => $expectedCanon, ':id' => $c['id']]);
                }
            }
        } catch (Exception $e) {
            // Silently continue if column not yet added
        }

        // Auto-detect and auto-upgrade canonical_url for all departments in database
        try {
            $allDepts = $db->query("SELECT id, slug, canonical_url FROM departments")->fetchAll(PDO::FETCH_ASSOC);
            $updateDeptStmt = $db->prepare("UPDATE departments SET canonical_url = :canon WHERE id = :id");
            foreach ($allDepts as $d) {
                $expectedCanon = clean_dept_url($d['slug'], true);
                if (empty($d['canonical_url']) || $d['canonical_url'] !== $expectedCanon) {
                    $updateDeptStmt->execute([':canon' => $expectedCanon, ':id' => $d['id']]);
                }
            }
        } catch (Exception $e) {
            // Silently continue if column not yet added
        }

        return ['success' => true, 'synced_count' => $synced];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}





/**
 * Get site setting with dot-notation support and caching
 *
 * Supports nested access like: get_site_setting('general.branches', [])
 * Also caches settings in memory for performance
 *
 * @param string $key Dot-notation key (e.g. 'general.branches')
 * @param mixed $default Default value if key not found
 * @param bool $refresh Force refresh cache
 * @return mixed The setting value or default
 *
 * Examples:
 * - get_site_setting('general.promo_banner_url', '')
 * - get_site_setting('general.branches', [])
 * - get_site_setting('general.course_facilities', [])
 */
function get_site_setting(string $key, $default = null, bool $refresh = false) {
    // Use existing cache from get_setting()
    $allSettings = get_setting('', null, $refresh);

    // Handle dot notation: 'general.branches'
    if (str_contains($key, '.')) {
        $parts = explode('.', $key, 2);
        $group = $parts[0];
        $subkey = $parts[1];

        // First try to get from group setting (JSON object)
        if (isset($allSettings[$group]) && is_array($allSettings[$group])) {
            if (isset($allSettings[$group][$subkey])) {
                return $allSettings[$group][$subkey];
            }
        }

        // Fallback: try as flat key (e.g., 'general_branches')
        $flatKey = str_replace('.', '_', $key);
        if (isset($allSettings[$flatKey])) {
            return $allSettings[$flatKey];
        }

        // Fallback: try the subkey as standalone key
        if (isset($allSettings[$subkey])) {
            return $allSettings[$subkey];
        }
    } else {
        // Simple key lookup
        if (isset($allSettings[$key])) {
            return $allSettings[$key];
        }
    }

    return $default;
}
