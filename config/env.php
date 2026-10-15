<?php
/**
 * Environment Variables Loader
 * Loads .env file and makes variables available via getenv()
 */

if (!defined('ENV_LOADED')) {
    define('ENV_LOADED', true);
}

/**
 * Load .env file from project root
 */
function load_env(string $path = null): void {
    if ($path === null) {
        $path = __DIR__ . '/../.env';
    }

    if (!file_exists($path)) {
        // In production, throw error if .env is missing
        if (getenv('APP_ENV') === 'production') {
            throw new RuntimeException('.env file not found. Copy .env.example to .env and configure it.');
        }
        // In development, show warning
        error_log('WARNING: .env file not found at ' . $path);
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

/**
 * Get environment variable with fallback
 *
 * @param string $key Variable name
 * @param mixed $default Default value if not set
 * @return mixed
 */
function env(string $key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convert string boolean to actual boolean
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }

    return $value;
}

// Auto-load .env when this file is included
load_env();

// Align PHP timezone across development and production
$appTz = env('APP_TIMEZONE', 'Asia/Dhaka');
if ($appTz) {
    @date_default_timezone_set($appTz);
}
