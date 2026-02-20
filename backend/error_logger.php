<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Error Logger Utility
 * Captures and logs errors to a file for debugging
 */

// Define log file path
define('ERROR_LOG_FILE', __DIR__ . '/logs/errors.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

/**
 * Log an error message
 * @param string $message Error message
 * @param string $level Error level (ERROR, WARNING, INFO, DEBUG)
 * @param array $context Additional context data
 */
function log_error($message, $level = 'ERROR', $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
    $logLine = "[$timestamp] [$level] $message$contextStr\n";
    
    // Append to log file
    file_put_contents(ERROR_LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
}

/**
 * Log a PHP error (for set_error_handler)
 */
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $levels = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
    ];
    
    $level = $levels[$errno] ?? 'UNKNOWN';
    log_error($errstr, $level, ['file' => $errfile, 'line' => $errline]);
    
    // Don't execute PHP internal error handler
    return false;
}

/**
 * Log uncaught exceptions
 */
function custom_exception_handler($exception) {
    log_error(
        $exception->getMessage(),
        'EXCEPTION',
        [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => array_slice($exception->getTrace(), 0, 3)
        ]
    );
}

// Register handlers
set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');

// Log fatal errors on shutdown
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        log_error($error['message'], 'FATAL', ['file' => $error['file'], 'line' => $error['line']]);
        
        // Notify Admin of Fatal Crash
        require_once 'utils_bot.php';
        notify_bot_admin([
            'type' => 'system_error',
            'message' => $error['message'],
            'file' => basename($error['file']), // Basename for brevity/security
            'line' => $error['line'],
            'uid' => 'SYSTEM'
        ]);
    }
});
