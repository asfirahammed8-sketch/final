<?php
/**
 * Centralized Error & Exception Handler
 * Creative IT Institute - DreamIT Platform
 *
 * Include this file ONCE, as early as possible (right after config/database.php).
 * It registers global exception and error handlers that:
 *   - Log all errors to storage/logs/
 *   - Show a generic user-facing message in production (APP_DEBUG=false)
 *   - Show detailed info in development (APP_DEBUG=true)
 *   - Never expose stack traces, DB credentials, or server paths to the client
 */

require_once __DIR__ . '/logger.php';

/**
 * Determine whether the current request expects a JSON response.
 */
function _is_json_request(): bool
{
    $accept  = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xrw     = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return str_contains($accept, 'application/json')
        || strtolower($xrw) === 'xmlhttprequest';
}

/**
 * Global uncaught exception handler.
 */
set_exception_handler(function (Throwable $e): void {
    $isDebug = defined('APP_DEBUG') && APP_DEBUG;

    // Log full details server-side
    AppLogger::error('Uncaught exception: ' . $e->getMessage(), [
        'exception' => get_class($e),
        'file'      => $e->getFile(),
        'line'      => $e->getLine(),
        // Stack trace only in debug log to avoid overly large log entries in production
        'trace'     => $isDebug ? $e->getTraceAsString() : null,
    ]);

    http_response_code(500);

    if (_is_json_request()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => 'SERVER_ERROR',
                'message' => $isDebug
                    ? $e->getMessage()
                    : 'An unexpected server error occurred. Please try again later.',
            ],
        ]);
        exit;
    }

    if ($isDebug) {
        echo '<h1 style="font-family:monospace;color:#991b1b;">Uncaught Exception</h1>';
        echo '<pre style="font-family:monospace;font-size:13px;background:#fef2f2;padding:16px;border-radius:6px;">';
        echo htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        // Serve the generic 404/500 page if it exists, otherwise minimal HTML
        $errorPage = dirname(__DIR__) . '/404.php';
        if (file_exists($errorPage)) {
            http_response_code(500);
            include $errorPage;
        } else {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Server Error</title></head>'
               . '<body style="font-family:sans-serif;text-align:center;padding:60px;">'
               . '<h1>Something went wrong</h1>'
               . '<p>We\'re working on fixing this. Please try again later.</p>'
               . '</body></html>';
        }
    }
    exit;
});

/**
 * Global fatal error handler (catches parse errors, out-of-memory, etc.)
 */
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        AppLogger::error('Fatal PHP error', [
            'type'    => $error['type'],
            'message' => $error['message'],
            'file'    => $error['file'],
            'line'    => $error['line'],
        ]);
    }
});

/**
 * Custom error handler — converts PHP notices/warnings to logged events.
 * Returns false for normal errors so PHP also logs them to its own log.
 */
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    $isDebug = defined('APP_DEBUG') && APP_DEBUG;

    // Only log warnings and above in production; log everything in debug
    if (!$isDebug && !in_array($errno, [E_WARNING, E_ERROR, E_USER_ERROR, E_USER_WARNING], true)) {
        return false;
    }

    AppLogger::warning("PHP Error [{$errno}]: {$errstr}", [
        'file' => $errfile,
        'line' => $errline,
    ]);

    // Return false so PHP continues its own error handling (important for E_NOTICE)
    return false;
});
