<?php
// admin/api_bot.php - Backend for Bot Management
session_start();
require_once '../db.php';
require_once '../config_telegram.php';

// One-time table setup check
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bot_broadcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT,
    image_url VARCHAR(255),
    button_text VARCHAR(100),
    button_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bot_broadcast_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    broadcast_id INT,
    tg_id BIGINT,
    message_id INT NULL,
    status ENUM('success', 'failed') DEFAULT 'success',
    error_msg TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(broadcast_id),
    INDEX(tg_id),
    INDEX(status)
)");

// Migrate existing table if needed
$check = mysqli_query($conn, "SHOW COLUMNS FROM bot_broadcast_logs LIKE 'status'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE bot_broadcast_logs ADD COLUMN status ENUM('success', 'failed') DEFAULT 'success', ADD COLUMN error_msg TEXT NULL");
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bot_reminder_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    trigger_hours INT,
    message TEXT,
    image_url VARCHAR(255) NULL,
    button_text VARCHAR(100),
    button_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Migrate existing table if needed
$check = mysqli_query($conn, "SHOW COLUMNS FROM bot_reminder_rules LIKE 'image_url'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE bot_reminder_rules ADD COLUMN image_url VARCHAR(255) NULL AFTER message");
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bot_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Seed default settings
mysqli_query($conn, "INSERT IGNORE INTO bot_settings (setting_key, setting_value) VALUES 
('welcome_message', 'Welcome to Paxyo!\nYour account has been successfully created. If you need help with services, orders, or balance, please contact support anytime t.me/paxyo'),
('maintenance_mode', '0'),
('maintenance_message', 'We are currently updating our systems. Please check back in a few minutes!')");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bot_auto_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(100),
    reply TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bot_reminder_sent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
    rule_id INT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id),
    INDEX(rule_id)
)");


// Auth check
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

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
    // Skip SSL verification for local XAMPP setups if needed
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        return ['ok' => false, 'description' => 'CURL Error: ' . $error];
    }
    
    return json_decode($response, true);
}

// 1. GET BOT STATUS
if ($action === 'get_bot_status') {
    $me = telegram_api('getMe');
    $webhook = telegram_api('getWebhookInfo');
    
    $user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM auth"))['count'];
    $active_24h = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM auth WHERE last_seen > DATE_SUB(NOW(), INTERVAL 1 DAY)"))['count'];
    $joined_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM auth WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)"))['count'];
    $with_balance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM auth WHERE balance > 0"))['count'];
    
    echo json_encode([
        'success' => true,
        'bot' => $me['result'] ?? null,
        'webhook' => $webhook['result'] ?? null,
        'user_count' => $user_count,
        'stats' => [
            'active_24h' => $active_24h,
            'joined_today' => $joined_today,
            'with_balance' => $with_balance
        ]
    ]);
    exit;
}

// 2. SET WEBHOOK
if ($action === 'set_webhook') {
    $custom_url = $input['webhook_url'] ?? $_POST['webhook_url'] ?? '';
    
    if (!empty($custom_url)) {
        $webhook_url = $custom_url;
    } else {
        // Improved protocol detection for proxies/Cloudflare
        $protocol = "http";
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            $protocol = "https";
        }
        
        $host = $_SERVER['HTTP_HOST'];
        // Remove /admin/api_bot.php and replace with /tg_webhook_handler.php
        // Note: We'll assume the files are in the same relative path structure
        $webhook_url = $protocol . "://" . $host . dirname($_SERVER['PHP_SELF'], 2) . "/tg_webhook_handler.php";
        // Clean up double slashes if any
        $webhook_url = str_replace(['//tg_webhook_handler.php', '///'], ['/tg_webhook_handler.php', '//'], $webhook_url);
    }
    
    if (strpos($webhook_url, 'https://') !== 0) {
        echo json_encode(['success' => false, 'error' => 'Telegram requires an HTTPS URL. Current: ' . $webhook_url]);
        exit;
    }
    
    $res = telegram_api('setWebhook', ['url' => $webhook_url]);
    
    if ($res['ok']) {
        echo json_encode(['success' => true, 'message' => 'Webhook successfully set to ' . $webhook_url]);
    } else {
        echo json_encode(['success' => false, 'error' => $res['description'] ?? 'Failed to set webhook']);
    }
    exit;
}

// 3. DELETE WEBHOOK
if ($action === 'delete_webhook') {
    $res = telegram_api('deleteWebhook');
    if ($res['ok']) {
        echo json_encode(['success' => true, 'message' => 'Webhook deleted']);
    } else {
        echo json_encode(['success' => false, 'error' => $res['description'] ?? 'Failed to delete webhook']);
    }
    exit;
}

// 4. BROADCAST MESSAGE
if ($action === 'broadcast') {
    $message = $input['message'] ?? $_POST['message'] ?? '';
    $image_url = $input['image_url'] ?? $_POST['image_url'] ?? '';
    $btn_text = $input['button_text'] ?? $_POST['button_text'] ?? 'Start App';
    $btn_url = $input['button_url'] ?? $_POST['button_url'] ?? 'https://paxyo.com/smm.php';
    
    if (empty($message) && empty($image_url)) {
        echo json_encode(['success' => false, 'error' => 'Message or Image is required']);
        exit;
    }

    // Save broadcast to DB
    $broadcast_id = db_insert('bot_broadcasts', [
        'message' => $message,
        'image_url' => $image_url,
        'button_text' => $btn_text,
        'button_url' => $btn_url
    ]);

    if (!$broadcast_id) {
         echo json_encode(['success' => false, 'error' => 'Failed to save broadcast to database']);
         exit;
    }
    
    // Prepare Keyboard if button exists
    $reply_markup = null;
    if (!empty($btn_text) && !empty($btn_url)) {
        $reply_markup = json_encode([
            'inline_keyboard' => [[
                ['text' => $btn_text, 'web_app' => ['url' => $btn_url]]
            ]]
        ]);
    }
    
    // Get all user Telegram IDs
    $audience = $input['audience'] ?? 'all';
    $where = "WHERE tg_id IS NOT NULL";
    
    if ($audience === 'active') {
        $where .= " AND last_seen > DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($audience === 'with_balance') {
        $where .= " AND balance > 0";
    } elseif ($audience === 'blocked') {
        $where .= " AND is_blocked = 1";
    }

    $users = db_query("SELECT * FROM auth $where");
    
    $success_count = 0;
    $fail_count = 0;
    $last_error = '';
    
    foreach ($users as $user) {
        // Personalized Message
        $final_message = $message;
        $final_message = str_replace('{first_name}', $user['first_name'] ?? 'User', $final_message);
        $final_message = str_replace('{username}', $user['username'] ?? 'User', $final_message);
        $final_message = str_replace('{balance}', number_format($user['balance'] ?? 0, 2), $final_message);
        $final_message = str_replace('{id}', $user['tg_id'], $final_message);

        $method = !empty($image_url) ? 'sendPhoto' : 'sendMessage';
        $params = [
            'chat_id' => $user['tg_id'],
            'parse_mode' => 'HTML'
        ];
        
        if ($method === 'sendPhoto') {
            $params['photo'] = $image_url;
            $params['caption'] = $final_message;
        } else {
            $params['text'] = $final_message;
        }
        
        if ($reply_markup) {
            $params['reply_markup'] = $reply_markup;
        }
        
        $res = telegram_api($method, $params);
        
        if ($res['ok']) {
            $success_count++;
            db_insert('bot_broadcast_logs', [
                'broadcast_id' => $broadcast_id,
                'tg_id' => $user['tg_id'],
                'message_id' => $res['result']['message_id'],
                'status' => 'success'
            ]);
        } else {
            $fail_count++;
            $last_error = $res['description'] ?? 'Unknown Telegram error';
            db_insert('bot_broadcast_logs', [
                'broadcast_id' => $broadcast_id,
                'tg_id' => $user['tg_id'],
                'status' => 'failed',
                'error_msg' => $last_error
            ]);
        }
        
        // Small delay to avoid hitting rate limits
        usleep(50000); 
    }
    
    $msg = "Broadcast complete. Sent: $success_count, Failed: $fail_count";
    if ($fail_count > 0) {
        $msg .= ". Last error: $last_error";
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $msg
    ]);
    exit;
}

// 5. GET BROADCASTS
if ($action === 'get_broadcasts') {
    $broadcasts = db_query("SELECT * FROM bot_broadcasts ORDER BY created_at DESC LIMIT 50");
    
    foreach ($broadcasts as &$b) {
        $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bot_broadcast_logs WHERE broadcast_id = " . $b['id']))['count'];
        $b['sent_count'] = $count;
    }
    
    echo json_encode(['success' => true, 'broadcasts' => $broadcasts]);
    exit;
}

// 6. DELETE BROADCAST MESSAGES (Withdraw)
if ($action === 'delete_broadcast_messages') {
    $broadcast_id = intval($input['broadcast_id'] ?? 0);
    if (!$broadcast_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid broadcast ID']);
        exit;
    }
    
    $logs = db_query("SELECT * FROM bot_broadcast_logs WHERE broadcast_id = $broadcast_id");
    
    $deleted_count = 0;
    foreach ($logs as $log) {
        $res = telegram_api('deleteMessage', [
            'chat_id' => $log['tg_id'],
            'message_id' => $log['message_id']
        ]);
        
        if ($res['ok'] || (isset($res['description']) && strpos($res['description'], "message to delete not found") !== false)) {
            $deleted_count++;
            // Delete log entry
            mysqli_query($conn, "DELETE FROM bot_broadcast_logs WHERE id = " . $log['id']);
        }
        usleep(20000); 
    }
    
    echo json_encode(['success' => true, 'message' => "Successfully retracted $deleted_count messages."]);
    exit;
}

// 7. DELETE ALL PREVIOUS BOT MESSAGES
if ($action === 'delete_all_messages') {
    $logs = db_query("SELECT * FROM bot_broadcast_logs ORDER BY created_at DESC LIMIT 500");
    
    $deleted_count = 0;
    foreach ($logs as $log) {
        $res = telegram_api('deleteMessage', [
            'chat_id' => $log['tg_id'],
            'message_id' => $log['message_id']
        ]);
        
        if ($res['ok'] || (isset($res['description']) && strpos($res['description'], "message to delete not found") !== false)) {
            $deleted_count++;
            mysqli_query($conn, "DELETE FROM bot_broadcast_logs WHERE id = " . $log['id']);
        }
        usleep(20000);
    }
    
    echo json_encode(['success' => true, 'message' => "Deleted $deleted_count messages from history."]);
    exit;
}

// 8. UPDATE BROADCAST MESSAGE (Edit)
if ($action === 'edit_broadcast_messages') {
    $broadcast_id = intval($input['broadcast_id'] ?? 0);
    $new_message = $input['message'] ?? '';
    
    if (!$broadcast_id || empty($new_message)) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }
    
    $logs = db_query("SELECT * FROM bot_broadcast_logs WHERE broadcast_id = $broadcast_id");
    $broadcast = db_select('bot_broadcasts', 'id', $broadcast_id);
    
    $updated_count = 0;
    foreach ($logs as $log) {
        $method = !empty($broadcast['image_url']) ? 'editMessageCaption' : 'editMessageText';
        $params = [
            'chat_id' => $log['tg_id'],
            'message_id' => $log['message_id'],
            'parse_mode' => 'HTML'
        ];
        
        if ($method === 'editMessageCaption') {
            $params['caption'] = $new_message;
        } else {
            $params['text'] = $new_message;
        }
        
        // Keep buttons if they existed
        if (!empty($broadcast['button_text']) && !empty($broadcast['button_url'])) {
            $params['reply_markup'] = json_encode([
                'inline_keyboard' => [[
                    ['text' => $broadcast['button_text'], 'web_app' => ['url' => $broadcast['button_url']]]
                ]]
            ]);
        }
        
        $res = telegram_api($method, $params);
        if ($res['ok']) $updated_count++;
        
        usleep(20000);
    }
    
    // Update master record
    mysqli_query($conn, "UPDATE bot_broadcasts SET message = '" . mysqli_real_escape_string($conn, $new_message) . "' WHERE id = $broadcast_id");
    
    echo json_encode(['success' => true, 'message' => "Successfully updated $updated_count messages."]);
    exit;
}


// 9. DIRECT MESSAGE TO USER
if ($action === 'direct_message') {
    $target_id = $input['tg_id'] ?? 0;
    $message = $input['message'] ?? '';
    
    if (!$target_id || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'User ID and Message are required']);
        exit;
    }

    // Get user info for personalization
    $user = db_select('auth', 'tg_id', $target_id);
    if ($user) {
        $message = str_replace('{first_name}', $user['first_name'] ?? 'User', $message);
        $message = str_replace('{username}', $user['username'] ?? 'User', $message);
        $message = str_replace('{id}', $user['tg_id'], $message);
    }
    
    $res = telegram_api('sendMessage', [
        'chat_id' => $target_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ]);
    
    if ($res['ok']) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => $res['description'] ?? 'Failed to send message']);
    }
    exit;
}

// 10. GET REMINDER RULES
if ($action === 'get_reminder_rules') {
    $rules = db_query("SELECT * FROM bot_reminder_rules ORDER BY trigger_hours ASC");
    echo json_encode(['success' => true, 'rules' => $rules]);
    exit;
}

// 11. SAVE REMINDER RULE
if ($action === 'save_reminder_rule') {
    $name = $input['name'] ?? '';
    $hours = intval($input['hours'] ?? 0);
    $message = $input['message'] ?? '';
    $btn_text = $input['button_text'] ?? '';
    $btn_url = $input['button_url'] ?? '';
    $id = intval($input['id'] ?? 0);

    if (empty($name) || $hours <= 0 || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Name, Hours and Message are required']);
        exit;
    }

    $data = [
        'name' => $name,
        'trigger_hours' => $hours,
        'message' => $message,
        'image_url' => $input['image_url'] ?? '',
        'button_text' => $btn_text,
        'button_url' => $btn_url
    ];

    if ($id > 0) {
        $res = db_update('bot_reminder_rules', 'id', $id, $data);
    } else {
        $res = db_insert('bot_reminder_rules', $data);
    }

    echo json_encode(['success' => true, 'message' => 'Reminder rule saved!']);
    exit;
}

// 12. DELETE REMINDER RULE
if ($action === 'delete_reminder_rule') {
    $id = intval($input['id'] ?? 0);
    if ($id) {
        db_delete('bot_reminder_rules', 'id', $id);
        // Also clean up logs if you want
        mysqli_query($conn, "DELETE FROM bot_reminder_sent_logs WHERE rule_id = $id");
    }
    echo json_encode(['success' => true, 'message' => 'Reminder rule deleted!']);
    exit;
}

// 13. TOGGLE REMINDER RULE
if ($action === 'toggle_reminder_rule') {
    $id = intval($input['id'] ?? 0);
    $active = intval($input['active'] ?? 1);
    db_update('bot_reminder_rules', 'id', $id, ['is_active' => $active]);
    echo json_encode(['success' => true]);
    exit;
}

// 14. PROCESS REMINDERS (Trigger manually or via CRON)
if ($action === 'process_reminders') {
    $rules = db_query("SELECT * FROM bot_reminder_rules WHERE is_active = 1");
    $sent_count = 0;

    foreach ($rules as $rule) {
        $hours = intval($rule['trigger_hours']);
        $rule_id = $rule['id'];

        // Find users who:
        // 1. Joined more than $hours ago
        // 2. Balance is 0
        // 3. Haven't received this specific rule yet
        $sql = "SELECT * FROM auth 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL $hours HOUR) 
                AND balance = 0 
                AND tg_id NOT IN (SELECT user_id FROM bot_reminder_sent_logs WHERE rule_id = $rule_id)
                LIMIT 50"; // Limit batch size to avoid timeout
        
        $users = db_query($sql);

        foreach ($users as $user) {
            $method = !empty($rule['image_url']) ? 'sendPhoto' : 'sendMessage';
            $params = [
                'chat_id' => $user['tg_id'],
                'parse_mode' => 'HTML'
            ];

            if ($method === 'sendPhoto') {
                $params['photo'] = $rule['image_url'];
                $params['caption'] = $msg;
            } else {
                $params['text'] = $msg;
            }

            if (!empty($rule['button_text']) && !empty($rule['button_url'])) {
                $params['reply_markup'] = json_encode([
                    'inline_keyboard' => [[
                        ['text' => $rule['button_text'], 'web_app' => ['url' => $rule['button_url']]]
                    ]]
                ]);
            }

            $res = telegram_api($method, $params);
            if ($res['ok']) {
                db_insert('bot_reminder_sent_logs', [
                    'user_id' => $user['tg_id'],
                    'rule_id' => $rule_id
                ]);
                $sent_count++;
            }
            usleep(50000); 
        }
    }

    echo json_encode(['success' => true, 'message' => "Process complete. Sent $sent_count reminders."]);
    exit;
}

// 15. GET BOT SETTINGS
if ($action === 'get_bot_settings') {
    $rows = db_query("SELECT * FROM bot_settings");
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
    echo json_encode(['success' => true, 'settings' => $settings]);
    exit;
}

// 16. SAVE BOT SETTINGS
if ($action === 'save_bot_settings') {
    $new_settings = $input['settings'] ?? [];
    foreach ($new_settings as $key => $val) {
        $key = mysqli_real_escape_string($conn, $key);
        $val = mysqli_real_escape_string($conn, $val);
        mysqli_query($conn, "INSERT INTO bot_settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    }
    echo json_encode(['success' => true, 'message' => 'Settings updated!']);
    exit;
}

// 17. GET AUTO REPLIES
if ($action === 'get_auto_replies') {
    $replies = db_query("SELECT * FROM bot_auto_replies ORDER BY keyword ASC");
    echo json_encode(['success' => true, 'replies' => $replies]);
    exit;
}

// 18. SAVE AUTO REPLY
if ($action === 'save_auto_reply') {
    $keyword = $input['keyword'] ?? '';
    $reply = $input['reply'] ?? '';
    if (empty($keyword) || empty($reply)) {
        echo json_encode(['success' => false, 'error' => 'Keyword and Reply are required']);
        exit;
    }
    $id = intval($input['id'] ?? 0);
    if ($id > 0) {
        db_update('bot_auto_replies', 'id', $id, ['keyword' => $keyword, 'reply' => $reply]);
    } else {
        db_insert('bot_auto_replies', ['keyword' => $keyword, 'reply' => $reply]);
    }
    echo json_encode(['success' => true, 'message' => 'Auto-reply saved!']);
    exit;
}

// 19. DELETE AUTO REPLY
if ($action === 'delete_auto_reply') {
    $id = intval($input['id'] ?? 0);
    if ($id) db_delete('bot_auto_replies', 'id', $id);
    echo json_encode(['success' => true]);
    exit;
}
// 20. GET BROADCAST REPORT (Analytics)
if ($action === 'get_broadcast_report') {
    $broadcast_id = intval($input['broadcast_id'] ?? 0);
    if (!$broadcast_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }

    $logs = db_query("SELECT l.*, a.first_name, a.username 
                     FROM bot_broadcast_logs l 
                     LEFT JOIN auth a ON l.tg_id = a.tg_id 
                     WHERE l.broadcast_id = $broadcast_id");
    
    echo json_encode(['success' => true, 'report' => $logs]);
    exit;
}

// 21. SEARCH USERS FOR DIRECT MESSAGE
if ($action === 'search_users') {
    $q = mysqli_real_escape_string($conn, $input['query'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'users' => []]);
        exit;
    }

    $users = db_query("SELECT tg_id, first_name, username FROM auth 
                       WHERE first_name LIKE '%$q%' OR username LIKE '%$q%' OR tg_id LIKE '%$q%' 
                       LIMIT 10");
    
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}
