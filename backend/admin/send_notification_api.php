<?php
// send_notification_api.php - API endpoint for admin notification sender
header('Content-Type: application/json');
include '../db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$target = $input['target'] ?? 'all';
$user_id = intval($input['user_id'] ?? 0);
$message = trim($input['message'] ?? '');

if (!$message) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Escape message
$message = $conn->real_escape_string($message);

try {
    if ($target === 'all') {
        // Send to all users
        $sql = "INSERT INTO user_alerts (user_id, message, is_read, created_at) 
                SELECT tg_id, '$message', 0, NOW() FROM auth";
        $conn->query($sql);
        $count = $conn->affected_rows;
        
        echo json_encode([
            'success' => true,
            'message' => "Notification sent to $count users!"
        ]);
    } else {
        // Send to specific user
        if (!$user_id) {
            echo json_encode(['error' => 'User ID is required']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO user_alerts (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => "Notification sent to user #$user_id!"
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
