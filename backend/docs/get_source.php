<?php
/**
 * Source Code Viewer API
 * Returns the source code of whitelisted backend PHP files for the documentation app.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Whitelist of files that can be viewed
$allowed_files = [
    'db.php',
    'index.php',
    'telegram_auth.php',
    'google_auth.php',
    'config_telegram.php',
    'config_google.php',
    'utils_bot.php',
    'get_service.php',
    'get_recommended.php',
    'process_order.php',
    'get_orders.php',
    'check_order_status.php',
    'order_manager.php',
    'user_actions.php',
    'deposit_handler.php',
    'get_deposits.php',
    'get_alerts.php',
    'mark_alerts_read.php',
    'chat_api.php',
    'chat_stream.php',
    'realtime_stream.php',
    'heartbeat.php',
    'webhook_handler.php',
    'cron_check_orders.php',
    'error_logger.php',
    'login.php',
    'logout.php',
    'bot.js',
    'api_save_phone.php',
    'api_check_phone.php',
    'updates.php',
    'websocket_server.php',
    'tg_webhook_handler.php',
];

$file = $_GET['file'] ?? '';

if (empty($file)) {
    echo json_encode(['error' => 'No file specified', 'available' => $allowed_files]);
    exit;
}

if (!in_array($file, $allowed_files)) {
    echo json_encode(['error' => 'File not allowed', 'available' => $allowed_files]);
    exit;
}

$filepath = dirname(__DIR__) . '/' . $file;

if (!file_exists($filepath)) {
    echo json_encode(['error' => 'File not found: ' . $file]);
    exit;
}

$content = file_get_contents($filepath);
$lines = substr_count($content, "\n") + 1;

echo json_encode([
    'success' => true,
    'file' => $file,
    'lines' => $lines,
    'size' => filesize($filepath),
    'modified' => date('Y-m-d H:i:s', filemtime($filepath)),
    'code' => $content
]);
