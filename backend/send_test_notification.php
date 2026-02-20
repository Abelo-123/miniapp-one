<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// send_test_notification.php - Send a test notification to verify real-time alerts
include 'db.php';

session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? 111;

// Insert a test notification
$message = "🎉 Test notification sent at " . date('H:i:s') . " - Your real-time system is working!";

$stmt = $conn->prepare("INSERT INTO user_alerts (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
$stmt->bind_param("is", $user_id, $message);

if ($stmt->execute()) {
    echo "✅ Test notification sent!\n";
    echo "Message: $message\n";
    echo "\nCheck your app - you should see this notification appear within 2 seconds!\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}
?>
