<?php
/**
 * Application Logger
 * Creative IT Institute - DreamIT Platform
 *
 * Writes structured log lines to storage/logs/app-YYYY-MM-DD.log
 *
 * Usage:
 *   AppLogger::info('message', ['key' => 'value']);
 *   AppLogger::warning('message');
 *   AppLogger::error('message', ['exception' => $e->getMessage()]);
 *   AppLogger::security('Admin login failed', ['email' => $email, 'ip' => $ip]);
 *
 * NEVER log passwords, session secrets, or other credential values.
 */

class AppLogger
{
    // Log levels
    const LEVEL_DEBUG    = 'DEBUG';
    const LEVEL_INFO     = 'INFO';
    const LEVEL_WARNING  = 'WARNING';
    const LEVEL_ERROR    = 'ERROR';
    const LEVEL_SECURITY = 'SECURITY';

    /**
     * Log informational messages (successful logins, admin actions, etc.)
     */
    public static function info(string $message, array $context = []): void
    {
        self::write(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log debug messages (development only — skipped in production)
     */
    public static function debug(string $message, array $context = []): void
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            self::write(self::LEVEL_DEBUG, $message, $context);
        }
    }

    /**
     * Log warning conditions
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log application errors
     */
    public static function error(string $message, array $context = []): void
    {
        self::write(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log security-relevant events: failed logins, CSRF mismatches, rate limiting, etc.
     * These entries are written regardless of APP_DEBUG.
     */
    public static function security(string $message, array $context = []): void
    {
        self::write(self::LEVEL_SECURITY, $message, $context);
    }

    /**
     * Internal: build log line and append to rotating daily log file.
     */
    private static function write(string $level, string $message, array $context): void
    {
        // Resolve log directory relative to project root
        $logDir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';

        // Build context string — redact any sensitive keys
        $safeContext = self::redact($context);
        $contextStr  = empty($safeContext) ? '' : ' ' . json_encode($safeContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ip     = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $uri    = $_SERVER['REQUEST_URI'] ?? 'cli';
        $line   = sprintf(
            "[%s] [%s] [ip:%s] [uri:%s] %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            $ip,
            $uri,
            $message,
            $contextStr
        );

        // Append to log file; suppress errors so logging failure never breaks the app
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

        // Also push ERROR / SECURITY entries to PHP error_log for syslog/cPanel log aggregation
        if (in_array($level, [self::LEVEL_ERROR, self::LEVEL_SECURITY], true)) {
            error_log("[DreamIT:{$level}] {$message}{$contextStr}");
        }
    }

    /**
     * Remove sensitive keys from context before logging.
     * Never log: password, token, secret, key, credential, auth
     */
    private static function redact(array $context): array
    {
        $sensitivePatterns = ['password', 'passwd', 'secret', 'token', 'key', 'auth', 'credential', 'card', 'cvv', 'pin'];
        $out = [];
        foreach ($context as $k => $v) {
            $lower = strtolower((string)$k);
            $isSensitive = false;
            foreach ($sensitivePatterns as $p) {
                if (str_contains($lower, $p)) {
                    $isSensitive = true;
                    break;
                }
            }
            $out[$k] = $isSensitive ? '[REDACTED]' : $v;
        }
        return $out;
    }
}
