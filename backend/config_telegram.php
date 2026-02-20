<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Telegram Bot Configuration
 * SECURITY: This file contains sensitive credentials - add to .gitignore
 */

// Your Telegram Bot Token from BotFather
define('TELEGRAM_BOT_TOKEN', '7547947738:AAFCrTdxp5EmLg5f39rrKn8kO5kLhA0Tekw');

// InitData expiration time in seconds (default: 24 hours)
define('TELEGRAM_INIT_DATA_EXPIRY', 86400);

// Admin Notification API (bot.js)
define('BOT_API_URL', 'https://paxyo-bot-ywuk.onrender.com/api/sendToJohn');
?>
