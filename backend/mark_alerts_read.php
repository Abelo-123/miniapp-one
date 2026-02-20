<?php
/**
 * Mark Alerts Read API - mark_alerts_read.php
 * 
 * Marks all unread alerts as read for the current user.
 * 
 * Method: GET
 * Request:  No params
 * Response: { "success": true }
 */

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
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Mark all unread alerts as read for this user
$user_id_safe = db_escape($user_id);
$sql = "UPDATE user_alerts SET is_read = 1 WHERE user_id = '$user_id_safe' AND is_read = 0";
mysqli_query($conn, $sql);

echo json_encode(['success' => true]);
?>
