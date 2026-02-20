<?php
/**
 * Check Order Status API - check_order_status.php
 * 
 * Triggers syncOrderStatuses() to batch-check active orders
 * against the external GodOfPanel API for the current user.
 * 
 * Method: GET
 * Request:  No params
 * Response: { "success": true, "checked": 5, "updated": 2, "updates": [...] }
 */

// Enable gzip compression
if (!ob_get_level()) ob_start('ob_gzhandler');

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header('Cache-Control: no-store, max-age=0');

include 'db.php';
require_once 'order_manager.php';

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

// Call the shared sync function from order_manager.php
$result = syncOrderStatuses($user_id, $conn);

echo json_encode([
    'success' => true,
    'checked' => $result['checked'],
    'updated' => $result['updated'],
    'updates' => $result['updates']
]);
?>
