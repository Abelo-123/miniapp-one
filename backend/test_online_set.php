<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db.php';
// Set user 111 to online (last_seen = now)
mysqli_query($conn, "UPDATE auth SET last_seen = NOW() WHERE tg_id = '111'");
echo "User 111 set to online";
?>
