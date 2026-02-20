<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// cron_bot_tasks.php - Processes automated bot tasks (Reminders, etc.)
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config_telegram.php';

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

echo "[" . date('Y-m-d H:i:s') . "] Starting Bot Tasks...\n";

// 1. PROCESS AUTO-REMINDERS
$rules = db_query("SELECT * FROM bot_reminder_rules WHERE is_active = 1");
$sent_count = 0;

foreach ($rules as $rule) {
    $hours = intval($rule['trigger_hours']);
    $rule_id = $rule['id'];

    // Find users who haven't deposited and haven't received this reminder
    $sql = "SELECT * FROM auth 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL $hours HOUR) 
            AND balance = 0 
            AND tg_id NOT IN (SELECT user_id FROM bot_reminder_sent_logs WHERE rule_id = $rule_id)
            LIMIT 20"; // Small batch per run
    
    $users = db_query($sql);

    foreach ($users as $user) {
        $msg = $rule['message'];
        $msg = str_replace('{first_name}', $user['first_name'] ?? 'User', $msg);
        
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
            echo "Sent reminder rule #$rule_id to user " . $user['tg_id'] . "\n";
        } else {
            echo "Failed to send to " . $user['tg_id'] . ": " . ($res['description'] ?? 'Unknown Error') . "\n";
        }
        usleep(100000); // 0.1s delay
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Bot Tasks done. Sent $sent_count reminders.\n";
