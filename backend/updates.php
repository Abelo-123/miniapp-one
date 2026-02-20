<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// updates.php
// Server-Sent Events endpoint for real-time order updates
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

include 'db.php';
include 'order_manager.php';

session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;
// Fallback
$user_id = $user_id ?: 111;

if (!$user_id) {
    echo "data: " . json_encode(['error' => 'Not authenticated']) . "\n\n";
    flush();
    exit;
}

// Close session to prevent lock, allowing other requests (navigation) to proceed
session_write_close();

$startTime = time();
$maxDuration = 40; // Increase duration to reduce "reconnect" frequency visible in network tab
$dbCheckInterval = 2; // Check local DB every 2 seconds (Fast)
$apiCheckInterval = 20; // Check External API every 20 seconds (Slow/Safe)
$lastApiCheck = 0;

// Helper to check DB change
function getLatestOrderHash($user_id, $conn) {
    // Determine if anything changed by hashing status + remains of active orders
    $stmt = $conn->prepare("SELECT id, status, remains FROM orders WHERE user_id = ? AND status IN ('pending', 'processing', 'in_progress')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = "";
    while($row = $res->fetch_assoc()) {
        $data .= $row['id'] . $row['status'] . $row['remains'];
    }
    return md5($data);
}

// Initial state
$lastHash = getLatestOrderHash($user_id, $conn);

// Helper to get balance
function getUserBalance($user_id, $conn) {
     $res = $conn->query("SELECT balance FROM auth WHERE tg_id = $user_id");
     if ($res && $row = $res->fetch_assoc()) {
         return floatval($row['balance']);
     }
     return 0;
}

$lastBalance = getUserBalance($user_id, $conn);

while (time() - $startTime < $maxDuration) {

    // 1. External API Sync (Periodically)
    // Only run if enough time passed since last check
    $updates = [];
    $updated_count = 0;
    
    if ((time() - $lastApiCheck) >= $apiCheckInterval) {
        $result = syncOrderStatuses($user_id, $conn); // This calls external API
        $lastApiCheck = time();
        if ($result['updated'] > 0) {
            $updates = $result['updates'];
            $updated_count = $result['updated'];
        }
    }

    // 2. Local DB Check (Fast polling)
    // Even if we didn't call API this loop, maybe another process did.
    // Check hash.
    $currentHash = getLatestOrderHash($user_id, $conn);
    $currentBalance = getUserBalance($user_id, $conn);
    
    $payload = [];
    
    if ($currentHash !== $lastHash || $updated_count > 0 || $currentBalance !== $lastBalance) {
        $lastHash = $currentHash;
        $lastBalance = $currentBalance;
        
        // If we didn't get updates from sync (e.g. just DB polling detected change),
        // we might not have the exact diff. For now, we assume if hash changed, client should re-fetch 
        // OR we can send a generic "refresh" command.
        // But since we have $updates from sync, let's use that if available.
        
        $payload['type'] = 'update';
        $payload['balance'] = $currentBalance;
        
        if (!empty($updates)) {
             $payload['updates'] = $updates;
             $payload['message'] = "Orders updated";
        } else {
             // Hash changed but we didn't mistakenly double-count sync?
             // Just tell client to refresh orders if we don't have the specific diff handy (lazy way)
             // or re-fetch active orders here.
             $payload['force_refresh'] = true; 
        }
        
        echo "data: " . json_encode($payload) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    } else {
        // Heartbeat
        echo "data: " . json_encode(['type' => 'heartbeat']) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    if (connection_aborted()) break;
    sleep($dbCheckInterval);
}

?>
