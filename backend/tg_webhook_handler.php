<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * tg_webhook_handler.php - Main Telegram Bot Webhook Handler
 * Processes incoming updates from Telegram and responds based on admin settings.
 */

require_once 'db.php';
require_once 'config_telegram.php';

// Log incoming update
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

// Ensure phone_number column exists in auth table
mysqli_query($conn, "ALTER TABLE auth ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) DEFAULT NULL");

/**
 * Send request to Telegram Bot API
 */
function telegram_api($method, $params = []) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

/**
 * Get bot setting from DB
 */
function get_bot_setting($key, $default = '') {
    global $conn;
    $res = mysqli_query($conn, "SELECT setting_value FROM bot_settings WHERE setting_key = '" . mysqli_real_escape_string($conn, $key) . "'");
    $row = mysqli_fetch_assoc($res);
    return $row ? $row['setting_value'] : $default;
}

/**
 * Check if user has phone number on record
 */
function user_has_phone($tg_id) {
    global $conn;
    $res = mysqli_query($conn, "SELECT phone_number FROM auth WHERE tg_id = '" . mysqli_real_escape_string($conn, $tg_id) . "'");
    $row = mysqli_fetch_assoc($res);
    return $row && !empty($row['phone_number']);
}

$message = $update['message'] ?? null;
if ($message) {
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $user = $message['from'];
    $tg_id = $user['id'];

    // Update user "Last Seen" and profile
    $first_name = mysqli_real_escape_string($conn, $user['first_name'] ?? 'User');
    $last_name = mysqli_real_escape_string($conn, $user['last_name'] ?? '');
    $username = mysqli_real_escape_string($conn, $user['username'] ?? '');
    
    mysqli_query($conn, "INSERT INTO auth (tg_id, first_name, last_name, username, created_at, last_seen) 
        VALUES ('$tg_id', '$first_name', '$last_name', '$username', NOW(), NOW()) 
        ON DUPLICATE KEY UPDATE last_seen = NOW(), first_name = VALUES(first_name), last_name = VALUES(last_name), username = VALUES(username)");

    // Handle Contact Sharing (Phone Number)
    if (isset($message['contact'])) {
        $contact = $message['contact'];
        $phone = mysqli_real_escape_string($conn, $contact['phone_number']);
        $contact_user_id = $contact['user_id'] ?? null;
        
        // Only save if user shared their own contact
        if ($contact_user_id == $tg_id) {
            mysqli_query($conn, "UPDATE auth SET phone_number = '$phone' WHERE tg_id = '$tg_id'");
            
            telegram_api('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "✅ <b>Phone number saved!</b>\n\nThank you for sharing your contact. Our support team can now reach you directly if needed.",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true])
            ]);
            
            // Send the app button
            $app_url = get_bot_setting('app_url', 'https://paxyo.com/smm.php');
            telegram_api('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "🚀 Ready to explore? Launch the app below!",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => '🦾 Launch SMM App', 'web_app' => ['url' => $app_url]]
                    ]]
                ])
            ]);
        }
        exit;
    }

    // Handle Start Command
    if (strpos($text, '/start') === 0) {
        $maintenance = get_bot_setting('maintenance_mode', '0');
        
        if ($maintenance === '1') {
            $maint_msg = get_bot_setting('maintenance_message', 'The bot is currently under maintenance. Please try again later.');
            telegram_api('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $maint_msg,
                'parse_mode' => 'HTML'
            ]);
        } else {
            $welcome = get_bot_setting('welcome_message', "Welcome to Paxyo!\nYour account has been successfully created. If you need help with services, orders, or balance, please contact support anytime t.me/paxyo");
            $welcome = str_replace('{first_name}', $user['first_name'] ?? 'User', $welcome);
            
            $app_url = get_bot_setting('app_url', 'https://paxyo.com/smm.php');
            
            // If user has no phone, send an optional request keyboard first
            if (!user_has_phone($tg_id)) {
                telegram_api('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "Welcome! 🇪🇹\n\n📱 <b>Optional:</b> Share your phone number to enable direct support:",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'keyboard' => [[
                            ['text' => '📱 Share Phone Number', 'request_contact' => true]
                        ], [
                            ['text' => '⏭️ Skip for now']
                        ]],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ])
                ]);
            }

            // Always show the welcome message with the App Button
            telegram_api('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $welcome,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => '🚀 Launch SMM App', 'web_app' => ['url' => $app_url]]
                    ]]
                ])
            ]);
        }
        exit;
    }
    
    // Handle "Skip for now" button
    if ($text === '⏭️ Skip for now') {
        $app_url = get_bot_setting('app_url', 'https://paxyo.com/smm.php');
        telegram_api('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "No problem! You can share your phone later from settings.\n\n🚀 Launch the app to get started:",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => '🦾 Launch SMM App', 'web_app' => ['url' => $app_url]]
                ]]
            ])
        ]);
        
        // Remove the keyboard
        telegram_api('sendMessage', [
            'chat_id' => $chat_id,
            'text' => '.',
            'reply_markup' => json_encode(['remove_keyboard' => true])
        ]);
        exit;
    }
    
    // Auto-Replies from Database
    $auto_replies = db_query("SELECT keyword, reply FROM bot_auto_replies WHERE is_active = 1");
    
    $lower_text = strtolower($text);
    foreach ($auto_replies as $row) {
        if (strpos($lower_text, strtolower($row['keyword'])) !== false) {
            telegram_api('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $row['reply'],
                'parse_mode' => 'HTML'
            ]);
            break; 
        }
    }
}
