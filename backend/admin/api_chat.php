<?php
/**
 * Admin Chat API - For admin to view and respond to user chats
 * 
 * This API allows admins to:
 * - List all users who have sent messages
 * - View messages from a specific user  
 * - Send replies to users
 */

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include '../db.php';
require_once '../config_telegram.php';

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

$messagesDir = dirname(__DIR__) . '/chat_data';

// Get all users who have chat messages
if ($action === 'get_users') {
    $users = [];
    
    if (is_dir($messagesDir)) {
        $files = glob($messagesDir . '/chat_*.json');
        
        if (!empty($files)) {
            $user_ids = [];
            $chat_data = [];
            
            foreach ($files as $file) {
                $userId = str_replace(['chat_', '.json'], '', basename($file));
                $user_ids[] = mysqli_real_escape_string($conn, $userId);
                
                $messages = json_decode(file_get_contents($file), true) ?: [];
                $lastMessage = end($messages);
                $unreadCount = count(array_filter($messages, fn($m) => $m['sender'] === 'user' && empty($m['read_by_admin'])));
                
                $chat_data[$userId] = [
                    'last_message' => $lastMessage['message'] ?? '',
                    'last_time' => $lastMessage['created_at'] ?? '',
                    'unread_count' => $unreadCount,
                    'message_count' => count($messages)
                ];
            }
            
            // Batch fetch user details
            $id_list = "'" . implode("','", $user_ids) . "'";
            $user_query = mysqli_query($conn, "SELECT tg_id, first_name, photo_url FROM auth WHERE tg_id IN ($id_list)");
            $user_details = [];
            while ($row = mysqli_fetch_assoc($user_query)) {
                $user_details[$row['tg_id']] = $row;
            }
            
            foreach ($chat_data as $userId => $data) {
                $details = $user_details[$userId] ?? null;
                $users[] = [
                    'user_id' => $userId,
                    'first_name' => $details['first_name'] ?? 'User ' . substr($userId, -4),
                    'photo_url' => $details['photo_url'] ?? 'https://ui-avatars.com/api/?name=U&background=6c5ce7&color=fff',
                    'last_message' => $data['last_message'],
                    'last_time' => $data['last_time'],
                    'unread_count' => $data['unread_count'],
                    'message_count' => $data['message_count']
                ];
            }
            
            // Sort by last message time (most recent first)
            usort($users, function($a, $b) {
                return strtotime($b['last_time'] ?: '0') - strtotime($a['last_time'] ?: '0');
            });
        }
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

// Get messages for a specific user
if ($action === 'get_messages') {
    $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['user_id'] ?? $input['user_id'] ?? '');
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    $file = $messagesDir . '/chat_' . $userId . '.json';
    $messages = [];
    
    if (file_exists($file)) {
        $messages = json_decode(file_get_contents($file), true) ?: [];
        
        // Mark all as read by admin
        foreach ($messages as &$msg) {
            if ($msg['sender'] === 'user') {
                $msg['read_by_admin'] = true;
            }
        }
        file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

// Admin sends a reply
if ($action === 'send_reply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['user_id'] ?? '');
    $message = trim($input['message'] ?? '');
    
    if (!$userId || !$message) {
        echo json_encode(['success' => false, 'error' => 'User ID and message required']);
        exit;
    }
    
    $file = $messagesDir . '/chat_' . $userId . '.json';
    $messages = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    
    // Add admin reply
    $messages[] = [
        'id' => uniqid(),
        'user_id' => $userId,
        'sender' => 'admin',
        'message' => $message,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
    
    // Also log to database for persistence
    if ($conn) {
        $escapedMessage = mysqli_real_escape_string($conn, $message);
        $escapedUserId = mysqli_real_escape_string($conn, $userId);
        
        // Ensure table exists (just in case)
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            sender ENUM('user', 'admin'),
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0,
            INDEX(user_id)
        )");

        mysqli_query($conn, "
            INSERT INTO chat_messages (user_id, sender, message, created_at) 
            VALUES ('$escapedUserId', 'admin', '$escapedMessage', NOW())
        ");
    }

    // Send Notification via Telegram Bot
    // This solves "DM user without username" requirement
    if (defined('TELEGRAM_BOT_TOKEN')) {
        $tgUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
        $tgData = [
            'chat_id' => $userId,
            'text' => "📩 <b>Support Reply:</b>\n\n" . $message . "\n\n<i>Reply here to continue chatting.</i>",
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tgUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tgData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        curl_exec($ch);
        curl_close($ch);
    }
    
    echo json_encode(['success' => true, 'message' => 'Reply sent']);
    exit;
}

// Admin closes/clears a chat
if ($action === 'close_chat') {
    $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['user_id'] ?? $input['user_id'] ?? '');
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    $file = $messagesDir . '/chat_' . $userId . '.json';
    $fileDeleted = false;
    if (file_exists($file)) {
        $fileDeleted = unlink($file);
    }
    
    $dbDeleted = false;
    if ($conn) {
        $escapedUserId = mysqli_real_escape_string($conn, $userId);
        $dbDeleted = mysqli_query($conn, "DELETE FROM chat_messages WHERE user_id = '$escapedUserId'");
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Chat history cleared successfully',
        'file_deleted' => $fileDeleted,
        'db_deleted' => $dbDeleted
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
