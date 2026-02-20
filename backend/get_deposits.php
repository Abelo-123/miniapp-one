<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Get Deposits API - Optimized for speed
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
$user_id = $user_id ?: 111;
session_write_close(); // Release session lock immediately

if (!$user_id) {
    echo '[]';
    exit;
}

// Fetch last 5 deposits with selective columns
$user_id = db_escape($user_id);
$sql = "SELECT id, amount, reference_id, status, created_at FROM deposits WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 5";
$deposits = db_query($sql);

echo json_encode($deposits ?: []);

