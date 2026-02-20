<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Paxyo Chat Stream - Real-time SSE for Chat
 * Watches for new messages in the user's chat file.
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;
if (!$user_id) {
    echo "data: " . json_encode(['error' => 'Not authenticated']) . "\n\n";
    exit;
}

session_write_close(); // Release session lock

$messagesDir = __DIR__ . '/chat_data';
$messagesFile = $messagesDir . '/chat_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $user_id) . '.json';

$last_mtime = 0;
if (file_exists($messagesFile)) {
    $last_mtime = filemtime($messagesFile);
}

// Initial full load
function sendMessages($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $messages = json_decode($content, true) ?: [];
        echo "data: " . json_encode(['type' => 'messages', 'messages' => $messages]) . "\n\n";
    } else {
        echo "data: " . json_encode(['type' => 'messages', 'messages' => []]) . "\n\n";
    }
}

sendMessages($messagesFile);
if (ob_get_level() > 0) ob_flush();
flush();

$start_time = time();
$max_duration = 120; // Longer duration for chat stability

while (time() - $start_time < $max_duration) {
    clearstatcache();
    
    if (file_exists($messagesFile)) {
        $mtime = filemtime($messagesFile);
        if ($mtime > $last_mtime) {
            $last_mtime = $mtime;
            sendMessages($messagesFile);
        }
    }
    
    // Heartbeat
    echo "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => time()]) . "\n\n";
    
    if (ob_get_level() > 0) ob_flush();
    flush();
    
    if (connection_aborted()) break;
    
    sleep(2);
}
?>
