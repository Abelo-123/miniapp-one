<?php
// admin/api_heartbeat.php - Lightweight status API for admin panel
session_start();
include '../db.php';

// Auth check
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Define online threshold (12 seconds for ultra-aggressive real-time)
$online_threshold = 12;

// Get total and online users
$user_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN last_seen > DATE_SUB(NOW(), INTERVAL $online_threshold SECOND) THEN 1 ELSE 0 END) as online
    FROM auth
"));

// Get pending orders count
$order_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'
"));

echo json_encode([
    'success' => true,
    'total_users' => (int)$user_stats['total'],
    'online_users' => (int)($user_stats['online'] ?? 0),
    'pending_orders' => (int)$order_stats['pending'],
    'server_time' => date('H:i:s')
]);
?>
