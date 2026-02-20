<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// realtime_stream.php - Unified SSE endpoint for notifications AND order updates
// Smart mode: Pauses when no active orders to save resources

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');


// Enable error logging, disable display to prevent stream corruption
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Catch fatal errors that bypass try-catch
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        http_response_code(200); // Force 200 so cPanel shows body
        echo "data: " . json_encode(['error' => 'Critical: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']]) . "\n\n";
        exit;
    }
});

try {
    require_once 'db.php';
} catch (Throwable $e) { // Catch ALL errors (Exception + Error)
    // Send 200 OK so the browser receives the error message (cPanel hides 500 bodies)
    http_response_code(200);
    echo "data: " . json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]) . "\n\n";
    exit;
}

session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? $_GET['uid'] ?? null;
// Fallback to match smm.php behavior (Demo Mode)
$user_id = $user_id ?: 111;

if (!$user_id) {
    echo "data: " . json_encode(['error' => 'Not authenticated']) . "\n\n";
    flush();
    exit;
}

session_write_close();

// Use LOCAL temp directory to avoid open_basedir issues on cPanel
$temp_dir = __DIR__ . '/temp';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// Initialize state tracking
$state_file = $temp_dir . "/paxyo_state_{$user_id}.json";
$flag_file = $temp_dir . '/paxyo_cron_active.flag';
$last_state = [];

if (file_exists($state_file)) {
    $last_state = json_decode(file_get_contents($state_file), true) ?? [];
}

$start_time = time();
$max_duration = 30;

// Helper: Get current state hash
function getCurrentState($user_id, $conn) {
    $state = [];
    
    // 1. Orders hash (only active orders)
    $stmt = $conn->prepare("
        SELECT GROUP_CONCAT(CONCAT(id,'|',status,'|',remains) ORDER BY id) as hash 
        FROM orders 
        WHERE user_id = ? AND status IN ('pending', 'processing', 'in_progress')
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $state['orders_hash'] = md5($row['hash'] ?? '');
    
    // 2. Alerts hash
    $stmt = $conn->prepare("
        SELECT GROUP_CONCAT(CONCAT(id,'|',is_read) ORDER BY id DESC) as hash 
        FROM user_alerts 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $state['alerts_hash'] = md5($row['hash'] ?? '');
    
    // 3. Balance
    $stmt = $conn->prepare("SELECT balance FROM auth WHERE tg_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $state['balance'] = $row ? floatval($row['balance']) : 0;
    
    // 4. Maintenance Status
    $res = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $row = mysqli_fetch_assoc($res);
    $state['maintenance_mode'] = $row ? $row['setting_value'] : '0';
    
    return $state;
}

// Helper: Get full data when change detected
function getFullData($user_id, $conn) {
    $data = [];
    
    // Get orders
    $stmt = $conn->prepare("
        SELECT id, api_order_id, service_name, link, quantity, charge, status, remains, start_count, created_at 
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data['orders'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['orders'][] = $row;
    }
    
    // Get alerts
    $stmt = $conn->prepare("
        SELECT id, message, is_read, created_at 
        FROM user_alerts 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data['alerts'] = [];
    $unread_count = 0;
    while ($row = $result->fetch_assoc()) {
        $data['alerts'][] = $row;
        if ($row['is_read'] == 0) $unread_count++;
    }
    $data['unread_count'] = $unread_count;
    
    // Get balance
    $stmt = $conn->prepare("SELECT balance FROM auth WHERE tg_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $data['balance'] = $row ? floatval($row['balance']) : 0;
    
    // Get Maintenance
    $res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('maintenance_mode', 'maintenance_allowed_ids')");
    $data['maintenance'] = ['mode' => '0', 'allowed_ids' => ''];
    while($row = mysqli_fetch_assoc($res)) {
        $data['maintenance'][$row['setting_key'] === 'maintenance_mode' ? 'mode' : 'allowed_ids'] = $row['setting_value'];
    }
    
    return $data;
}

// Main loop - Check for changes every 2 seconds
$idle_count = 0;
while (time() - $start_time < $max_duration) {
    
    // Check if cron is active (meaning there are active orders)
    $cron_active = file_exists($flag_file);
    
    // Always check for state changes, even if no orders are active
    // We only slow down the idle heartbeat, not the state check
    if (!$cron_active) {
        // No active orders - just mark idle for heartbeat purposes
        $idle_count++;
    } else {
        $idle_count = 0;
    }

    // Send idle heartbeat if needed (every 6 seconds if idle)
    if ($idle_count >= 3) {
        echo "data: " . json_encode(['type' => 'idle', 'message' => 'No active orders']) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
        $idle_count = 0;
    }
    
    // Continue with state check...
    
    $current_state = getCurrentState($user_id, $conn);
    
    // Check if ANYTHING changed
    $changed = false;
    $changes = [];
    
    if (!isset($last_state['orders_hash']) || $current_state['orders_hash'] !== $last_state['orders_hash']) {
        $changed = true;
        $changes[] = 'orders';
    }
    
    if (!isset($last_state['alerts_hash']) || $current_state['alerts_hash'] !== $last_state['alerts_hash']) {
        $changed = true;
        $changes[] = 'alerts';
    }
    
    if (!isset($last_state['balance']) || $current_state['balance'] !== $last_state['balance']) {
        $changed = true;
        $changes[] = 'balance';
    }

    if (!isset($last_state['maintenance_mode']) || $current_state['maintenance_mode'] !== $last_state['maintenance_mode']) {
        $changed = true;
        $changes[] = 'maintenance';
    }
    
    if ($changed) {
        // Something changed! Send full update
        $full_data = getFullData($user_id, $conn);
        
        $payload = [
            'type' => 'update',
            'timestamp' => time(),
            'changes' => $changes,
            'data' => $full_data
        ];
        
        echo "data: " . json_encode($payload) . "\n\n";
        
        // Save current state
        $last_state = $current_state;
        file_put_contents($state_file, json_encode($current_state));
        
        if (ob_get_level() > 0) ob_flush();
        flush();
    } else {
        // Nothing changed, send heartbeat
        echo "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => time()]) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }
    
    if (connection_aborted()) break;
    
    sleep(2);
}

// Clean up
if (file_exists($state_file)) {
    file_put_contents($state_file, json_encode($current_state ?? []));
}
?>
