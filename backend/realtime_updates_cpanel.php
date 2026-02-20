<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// realtime_updates_cpanel.php - SSE endpoint optimized for cPanel
// This works on shared hosting without WebSocket support

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

require_once 'db.php';

session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;
$user_id = $user_id ?: 111;

if (!$user_id) {
    echo "data: " . json_encode(['error' => 'Not authenticated']) . "\n\n";
    flush();
    exit;
}

session_write_close();

// Get last known state from session/cookie
$last_check_file = sys_get_temp_dir() . "/paxyo_user_{$user_id}_lastcheck.txt";
$last_hash = file_exists($last_check_file) ? file_get_contents($last_check_file) : '';

$start_time = time();
$max_duration = 25; // Keep connection alive for 25 seconds, then reconnect

function getOrdersHash($user_id, $conn) {
    $stmt = $conn->prepare("SELECT GROUP_CONCAT(CONCAT(id,status,remains) ORDER BY id) as hash FROM orders WHERE user_id = ? AND status IN ('pending', 'processing', 'in_progress')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return md5($row['hash'] ?? '');
}

function getUserBalance($user_id, $conn) {
    $stmt = $conn->prepare("SELECT balance FROM auth WHERE tg_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? floatval($row['balance']) : 0;
}

$last_balance = getUserBalance($user_id, $conn);

while (time() - $start_time < $max_duration) {
    $current_hash = getOrdersHash($user_id, $conn);
    $current_balance = getUserBalance($user_id, $conn);
    
    // Check if anything changed
    if ($current_hash !== $last_hash || $current_balance !== $last_balance) {
        // Fetch updated orders
        $stmt = $conn->prepare("SELECT id, api_order_id, service_name, link, quantity, charge, status, remains, start_count, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        $payload = [
            'type' => 'update',
            'orders' => $orders,
            'balance' => $current_balance,
            'timestamp' => time()
        ];
        
        echo "data: " . json_encode($payload) . "\n\n";
        
        $last_hash = $current_hash;
        $last_balance = $current_balance;
        file_put_contents($last_check_file, $current_hash);
        
        if (ob_get_level() > 0) ob_flush();
        flush();
    } else {
        // Heartbeat
        echo "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => time()]) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }
    
    if (connection_aborted()) break;
    
    sleep(2); // Check every 2 seconds
}

// Clean up
if (file_exists($last_check_file)) {
    file_put_contents($last_check_file, $current_hash);
}
?>
