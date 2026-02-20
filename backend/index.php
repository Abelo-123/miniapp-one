<?php
/**
 * Paxyo SMM - Main Entry Point (index.php)
 * 
 * Smart router that handles:
 * - Telegram Mini App users → SMM dashboard
 * - Authenticated web users → SMM dashboard
 * - Unauthenticated web users → Login page
 */

// --- CRITICAL HEADERS (Required for Telegram Mini App) ---
header("Access-Control-Allow-Origin: *");
header("Content-Security-Policy: frame-ancestors *");
header("X-Frame-Options: ALLOWALL");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Define constant to prevent direct access to sub-files if needed, or to skip redirects
define('IN_INDEX', true);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
    session_start();
}

// --- DETECT TELEGRAM MINI APP ---
$is_telegram = false;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

// Check for Telegram-specific indicators (Robust Detection)
if (stripos($user_agent, 'Telegram') !== false || 
    stripos($referer, 't.me') !== false || 
    stripos($referer, 'telegram.org') !== false ||
    stripos($x_requested_with, 'telegram') !== false ||
    isset($_GET['tgWebAppData']) ||
    isset($_GET['tgWebAppStartParam']) ||
    isset($_GET['tgWebAppVersion']) ||
    isset($_GET['id'])) { // If an ID is passed, assume it's a Telegram deep link or similar
    $is_telegram = true;
}

// --- CHECK AUTHENTICATION ---
$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;

// Handle URL parameter (from Telegram deep links)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = $_GET['id'];
    $_SESSION['tg_id'] = $user_id;
    // Set a cookie for persistent web access if they visited via a deep link
    setcookie("id", $user_id, time() + (86400 * 30), "/", "", true, true);
}

// --- ROUTING LOGIC ---

// If user is from Telegram, ALWAYS show the SMM app (Pass Automatically)
// Authentication for Mini Apps happens via JS in smm.php
if ($is_telegram) {
    include 'smm.php';
    exit;
}

// If user is already authenticated (has session/cookie), show SMM app
if ($user_id) {
    include 'smm.php';
    exit;
}

// Unauthenticated web users → Pass to SMM app automatically (Guest Mode)
include 'smm.php';
exit;
?>