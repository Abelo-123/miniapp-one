<?php
/**
 * Get Alerts API - get_alerts.php
 * 
 * Returns user's notifications with unread count.
 * 
 * Method: GET
 * Request:  No params
 * Response: { "alerts": [...], "unread_count": 3 }
 */

// Enable gzip compression
if (!ob_get_level()) ob_start('ob_gzhandler');

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header('Cache-Control: no-store, max-age=0');

include 'db.php';

// Session: Read and immediately release lock
session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;
$user_id = $user_id ?: 111; // Fallback for testing
session_write_close(); // Release session lock immediately

if (!$user_id) {
    echo json_encode(['alerts' => [], 'unread_count' => 0]);
    exit;
}

// Fetch last 20 alerts for user
$user_id_safe = db_escape($user_id);
$sql = "SELECT id, message, is_read, created_at 
        FROM user_alerts 
        WHERE user_id = '$user_id_safe' 
        ORDER BY created_at DESC 
        LIMIT 20";

$alerts = db_query($sql) ?: [];

// Count unread alerts
$unread_count = 0;
foreach ($alerts as $alert) {
    if (intval($alert['is_read']) === 0) {
        $unread_count++;
    }
}

echo json_encode([
    'alerts' => $alerts,
    'unread_count' => $unread_count
]);
?>
