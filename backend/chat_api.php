<?php
/**
 * Paxyo Chat API - Lightweight Live Chat System
 * 
 * This API handles chat between users and admin.
 * Messages are stored in a simple JSON file for lightweight operation.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

// Get user ID
$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Messages file path (per-user)
$messagesDir = __DIR__ . '/chat_data';
if (!is_dir($messagesDir)) {
    mkdir($messagesDir, 0755, true);
}
$messagesFile = $messagesDir . '/chat_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $user_id) . '.json';

// Load messages helper
function loadMessages($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    return [];
}

// Save messages helper
function saveMessages($file, $messages) {
    file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
}

// Get action
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'send';
}

// Handle GET - Fetch messages
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $messages = loadMessages($messagesFile);
    
    // Limit to last 50 messages
    $messages = array_slice($messages, -50);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    exit;
}

// Handle POST - Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        exit;
    }
    
    // Limit message length
    if (strlen($message) > 1000) {
        echo json_encode(['success' => false, 'error' => 'Message too long']);
        exit;
    }
    
    // Load existing messages
    $messages = loadMessages($messagesFile);
    
    // Add new message
    $messages[] = [
        'id' => uniqid(),
        'user_id' => $user_id,
        'sender' => 'user',
        'message' => $message,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Keep only last 100 messages per user in file
    if (count($messages) > 100) {
        $messages = array_slice($messages, -100);
    }
    
    // Save messages
    saveMessages($messagesFile, $messages);
    
    // Also log to database for admin to see
    if ($conn) {
        // Ensure table exists
        $createTableSql = "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            sender ENUM('user', 'admin'),
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0,
            INDEX(user_id)
        )";
        mysqli_query($conn, $createTableSql);

        $escapedMessage = mysqli_real_escape_string($conn, $message);
        $escapedUserId = mysqli_real_escape_string($conn, $user_id);
        
        mysqli_query($conn, "
            INSERT INTO chat_messages (user_id, sender, message, created_at) 
            VALUES ('$escapedUserId', 'user', '$escapedMessage', NOW())
        ");
    }

    // Notify Admin via Bot
    require_once 'utils_bot.php';
    notify_bot_admin([
        'type' => 'chat',
        'uid' => $user_id,
        'message' => $message
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent'
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
