<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// utils_bot.php - Helper functions for Bot Notifications

function notify_bot_admin($data) {
    // Log the notification to a file for now since we don't have the bot token configured yet
    $logFile = __DIR__ . '/bot_notifications.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = json_encode($data, JSON_PRETTY_PRINT);
    
    file_put_contents($logFile, "[$timestamp] Check: $message\n\n", FILE_APPEND);
    
    // In production, you would use curl to send to your Telegram Bot API
    // $botToken = 'YOUR_BOT_TOKEN';
    // $chatId = 'YOUR_ADMIN_CHAT_ID';
    // ... curl request ...
    
    return true;
}
?>
