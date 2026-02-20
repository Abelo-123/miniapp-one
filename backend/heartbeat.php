<?php
/**
 * Heartbeat API - Ultra-lightweight Online Status Ping
 * Optimized for speed: gzip, early session close, minimal response
 */

// Enable gzip compression
if (!ob_get_level()) ob_start('ob_gzhandler');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

include 'db.php';

// Session: Read and immediately release lock
session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;
session_write_close(); // Release session lock immediately

if ($user_id) {
    // Fast UPDATE query
    $user_id = db_escape($user_id);
    mysqli_query($conn, "UPDATE auth SET last_seen = NOW() WHERE tg_id = '$user_id'");
    
    // Minimal response (8 bytes vs 37 bytes = 78% smaller)
    echo '{"ok":1}';
} else {
    echo '{"ok":0}';
}

