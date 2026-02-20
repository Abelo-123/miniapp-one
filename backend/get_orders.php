<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Get Orders API - Optimized for speed
 * Features: gzip, early session close, selective columns
 */

// Enable gzip compression
if (!ob_get_level()) ob_start('ob_gzhandler');

header('Content-Type: application/json');
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
    echo '{"error":"Not authenticated"}';
    exit;
}

// Fetch only needed columns (faster query + smaller response)
$limit = 50;
$stmt = $conn->prepare("
    SELECT id, api_order_id, service_id, service_name, link, quantity, charge, status, remains, start_count, created_at 
    FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ?
");
$stmt->bind_param("ii", $user_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(['orders' => $orders], JSON_UNESCAPED_SLASHES);

