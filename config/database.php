<?php
/**
 * Database Configuration & Connection Handler
 * Creative IT Institute - DreamIT Platform
 *
 * Compliant with production best-practices:
 * - Reads environment variables or root .env
 * - Uses PDO with prepared statements, UTF-8 mb4, and exceptions
 * - Does NOT auto-run setup/migrations on connection
 */

// Simple robust .env parser
if (file_exists(dirname(__DIR__) . '/.env')) {
    $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Strip quotes if wrapped
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if (!getenv($key)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Define configuration constants with fallback defaults
if (!defined('APP_ENV')) define('APP_ENV', getenv('APP_ENV') ?: 'local');
if (!defined('APP_DEBUG')) define('APP_DEBUG', (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1'));
if (!defined('CANONICAL_URL')) {
    $envCanon = getenv('CANONICAL_URL');
    if (!empty($envCanon) && $envCanon !== 'https://www.creativeitinstitute.com' && filter_var($envCanon, FILTER_VALIDATE_URL)) {
        define('CANONICAL_URL', rtrim($envCanon, '/'));
    }
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'dreamit');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
// Support both DB_PASSWORD and DB_PASS for compatibility
if (!defined('DB_PASS')) {
    $dbPass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : getenv('DB_PASS');
    define('DB_PASS', $dbPass !== false ? $dbPass : '');
}
if (!defined('DB_CHARSET')) define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    /**
     * Get singleton PDO connection instance
     * Pure connection only - never auto-seeds or migrates
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                try {
                    $nowOffset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
                    self::$instance->exec("SET time_zone = '{$nowOffset}'");
                } catch (Exception $tzEx) {
                    // Graceful fallback if mysql timezone setting is restricted
                }
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                if (APP_DEBUG) {
                    throw new PDOException("Database connection error: " . $e->getMessage(), (int)$e->getCode());
                } else {
                    throw new PDOException("Unable to connect to the database service. Please contact the administrator.");
                }
            }
        }
        return self::$instance;
    }
}

/**
 * Global helper to obtain active DB connection
 */
function getDB(): PDO {
    return Database::getConnection();
}
