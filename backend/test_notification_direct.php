<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// test_notification_direct.php - Direct test of notification system
include 'db.php';

$user_id = 111; // Your test user
$message = "🎉 TEST: Real-time notification at " . date('H:i:s');

echo "Sending notification to user $user_id...\n";
echo "Message: $message\n\n";

$stmt = $conn->prepare("INSERT INTO user_alerts (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
$stmt->bind_param("is", $user_id, $message);

if ($stmt->execute()) {
    echo "✅ Notification inserted into database!\n";
    echo "ID: " . $stmt->insert_id . "\n\n";
    
    // Check if it's there
    $check = $conn->query("SELECT * FROM user_alerts WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1");
    $row = $check->fetch_assoc();
    
    echo "Verification:\n";
    print_r($row);
    
    echo "\n\n📱 Now check your app - notification should appear within 2 seconds!\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}
?>
