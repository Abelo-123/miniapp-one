<?php
// --- CRITICAL HEADERS (Required for Telegram Mini App) ---
header("Access-Control-Allow-Origin: *");
header("Content-Security-Policy: frame-ancestors *");
header("X-Frame-Options: ALLOWALL");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
    session_start();
}

include 'db.php';

// Detect if running inside Telegram Mini App
$is_telegram_app = false;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

// Check for Telegram-specific indicators (Case-Insensitive & Robust)
if (stripos($user_agent, 'Telegram') !== false || 
    stripos($referer, 't.me') !== false || 
    stripos($referer, 'telegram.org') !== false ||
    stripos($x_requested_with, 'telegram') !== false ||
    isset($_GET['tgWebAppData']) ||
    isset($_GET['tgWebAppStartParam']) ||
    isset($_GET['tgWebAppVersion']) ||
    isset($_GET['id'])) {
    $is_telegram_app = true;
}

// Get User ID from Session > Cookie > URL Param
$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;

// Get auth provider from session
$auth_provider = $_SESSION['auth_provider'] ?? null;

// Handle URL param if provided (from Telegram deep links)
if ((!$user_id || $user_id == '') && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $_SESSION['tg_id'] = $user_id;
    setcookie("id", $user_id, time()+86400*30, "/", "", true, true);
}

// Web users (non-Telegram) must be authenticated
// Telegram Mini App users will authenticate via initData (handled by JS)
// Logic updated: Allow guest access regardless of entry point
// if (!$is_telegram_app && !$user_id && !defined('IN_INDEX')) {
//     // Redirect to login page for web users
//     header('Location: login.php');
//     exit;
// }

// For Telegram users without session, set a temporary ID (will be replaced by JS auth)
if ($is_telegram_app && !$user_id) {
    $user_id = 0; // Temporary, will be authenticated via Telegram.WebApp.initData
}

// Check if user is blocked
if ($user_id) {
    $blockCheck = db('select', 'auth', 'tg_id', $user_id, 'is_blocked');
    if ($blockCheck == 1) {
        die('<div style="background:black; color:red; height:100vh; width:100vw; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold;">ACCESS DENIED</div>');
    }
}

// Fetch user balance
$balance = $user_id ? db('select', 'auth', 'tg_id', $user_id, 'balance') : 0;
$balance = $balance ?: 0;

// Get settings
$config_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('marquee_text', 'marquee_enabled', 'rate_multiplier', 'maintenance_mode', 'maintenance_allowed_ids')");
$settings = [];
while ($row = mysqli_fetch_assoc($config_res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$marquee_text = $settings['marquee_text'] ?? 'Welcome to Paxyo SMM!';
$marquee_enabled = ($settings['marquee_enabled'] ?? '1') === '1';
$rate_multiplier = floatval($settings['rate_multiplier'] ?? 400);

// Maintenance Mode Logic
$is_maintenance = ($settings['maintenance_mode'] ?? '0') === '1';
$allowed_ids = array_filter(array_map('trim', explode(',', $settings['maintenance_allowed_ids'] ?? '')));
$user_can_order = true;
if ($is_maintenance) {
    if (!in_array($user_id, $allowed_ids)) {
        $user_can_order = false;
    }
}

// Check for active holiday
$today = date('Y-m-d');
$holiday_res = mysqli_query($conn, "SELECT name, discount_percent FROM holidays WHERE status = 'active' AND '$today' BETWEEN start_date AND end_date ORDER BY discount_percent DESC LIMIT 1");
$active_holiday = mysqli_fetch_assoc($holiday_res);
$discount_percent = $active_holiday ? floatval($active_holiday['discount_percent']) : 0;
$holiday_name = $active_holiday ? $active_holiday['name'] : '';

// User profile
$first_name = $_SESSION['tg_first_name'] ?? db('select', 'auth', 'tg_id', $user_id, 'first_name') ?? ('User ' . substr($user_id, -4));
$user_profile_img = $_SESSION['tg_photo_url'] ?? db('select', 'auth', 'tg_id', $user_id, 'photo_url') ?? ('https://ui-avatars.com/api/?name='.urlencode($first_name).'&background=random&color=fff');

// Get hidden services
$hiddenFile = __DIR__ . '/hidden_services.json';
$hidden_services = [];
if (file_exists($hiddenFile)) {
    $hidden_services = json_decode(file_get_contents($hiddenFile), true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0a0a0f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Paxyo SMM</title>
    
    <!-- Performance: DNS Prefetch & Preconnect for faster external resource loading -->
    <link rel="dns-prefetch" href="//telegram.org">
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="//ui-avatars.com">
    <link rel="preconnect" href="https://telegram.org" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
    
    <!-- Telegram Mini App SDK - Load FIRST for fastest detection -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    
    <!-- Critical CSS inline for faster First Contentful Paint -->
    <style>
        /* Critical inline styles for instant render */
        :root {
            --tg-theme-bg-color: #0a0a0f;
            --tg-theme-text-color: #ffffff;
            --tg-theme-button-color: #6c5ce7;
            --tg-theme-secondary-bg-color: #1a1a24;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        /* TELEGRAM MINI APP SCROLL FIX:
           html/body are locked. All scrolling happens inside .tg-scroll-wrapper */
        html, body { 
            background: var(--tg-theme-bg-color, #0a0a0f); 
            color: var(--tg-theme-text-color, #fff);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            width: 100%;
            height: 100vh;
            overflow: hidden;
            overscroll-behavior: none;
            position: fixed;
            top: 0;
            left: 0;
        }
        .tg-scroll-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-y: contain;
        }
        .tg-loading {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: var(--tg-theme-bg-color, #0a0a0f);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999; transition: opacity 0.2s ease;
        }
        .tg-loading.hidden { 
            opacity: 0; 
            pointer-events: none; 
            visibility: hidden;
            display: none !important; 
        }
        .tg-loading-spinner {
            width: 36px; height: 36px;
            border: 3px solid rgba(108, 92, 231, 0.2);
            border-top-color: #6c5ce7;
            border-radius: 50%;
            animation: tg-spin 0.6s linear infinite;
        }
        @keyframes tg-spin { to { transform: rotate(360deg); } }
    </style>
    
    <!-- Main CSS - versioned for instant reload -->
    <link rel="stylesheet" href="smm_styles.css?v=<?php echo time(); ?>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'smm-bg': '#0a0a0f',
                        'smm-card': '#1a1a24',
                        'smm-input': '#0d0d12',
                        'smm-accent': '#6c5ce7',
                        'smm-success': '#00d26a',
                        'smm-danger': '#ff4757',
                    }
                }
            }
        }
    </script>
    <style>
        /* Telegram Mini App specific styles */

        .tg-scroll-wrapper {
            /* Respect Telegram safe areas */
            padding-top: env(safe-area-inset-top);
            padding-bottom: env(safe-area-inset-bottom);
        }
        /* Removed redundant tg-loading styles - unified in critical block */
        @keyframes tg-spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <script>
        // Early Telegram WebApp initialization for faster loading
        (function() {
            if (window.Telegram && window.Telegram.WebApp) {
                const tg = window.Telegram.WebApp;
                // Signal Telegram that the app is loading
                tg.ready();
                // Expand to full height immediately
                tg.expand();
                // CRITICAL: Disable Telegram's vertical swipe interception
                if (tg.disableVerticalSwipes) {
                    tg.disableVerticalSwipes();
                }
                try { tg.isVerticalSwipesEnabled = false; } catch(e) {}
                // Apply Telegram theme colors immediately
                if (tg.themeParams) {
                    const root = document.documentElement;
                    root.style.setProperty('--tg-theme-bg-color', tg.themeParams.bg_color || '#0a0a0f');
                    root.style.setProperty('--tg-theme-text-color', tg.themeParams.text_color || '#ffffff');
                    root.style.setProperty('--tg-theme-button-color', tg.themeParams.button_color || '#6c5ce7');
                    root.style.setProperty('--tg-theme-secondary-bg-color', tg.themeParams.secondary_bg_color || '#1a1a24');
                }
            }
        })();
    </script>
</head>
<body>
    <!-- Telegram Loading Overlay - Shows while authenticating -->
    <div class="tg-loading" id="tg-loading">
        <div class="tg-loading-spinner"></div>
    </div>

    <!-- SCROLL WRAPPER: All page content goes inside this scrollable div -->
    <div class="tg-scroll-wrapper" id="tg-scroll-wrapper">

    <!-- SVG Definitions for Social Icons -->
    <svg style="display: none;">
        <defs>
            <radialGradient id="instagram-gradient" cx="30%" cy="107%" r="150%">
                <stop offset="0%" style="stop-color:#fdf497"/>
                <stop offset="5%" style="stop-color:#fdf497"/>
                <stop offset="45%" style="stop-color:#fd5949"/>
                <stop offset="60%" style="stop-color:#d6249f"/>
                <stop offset="90%" style="stop-color:#285AEB"/>
            </radialGradient>
        </defs>
    </svg>
    <script>
        // Check for saved theme preference
        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-mode');
        }
    </script>

    <div class="smm-container">
        <!-- Global Header - Fixed for all tabs -->
        <header class="global-header mb-2">
            <div class="flex justify-between items-center mb-2 pt-2">
                <div class="flex items-center gap-3">
                    <img src="<?php echo $user_profile_img; ?>" alt="Profile" class="w-12 h-12 rounded-full border-2 border-smm-accent object-cover">
                    <div>
                        <h1 class="text-base font-bold text-theme-primary leading-tight"><?php echo $_SESSION['tg_first_name'] ?? 'Guest'; ?></h1>
                        <p class="text-xs text-smm-accent font-medium"> <span id="user-balance" class="font-mono text-theme-primary text-sm whitespace-nowrap px-1 bg-smm-card rounded"><?php echo number_format($balance, 2); ?> ETB</span></p>
                    </div>
                </div>
                <div class="flex gap-3">
                     <!-- Notification Bell -->
                     <button class="w-10 h-10 rounded-full bg-smm-card hover:bg-gray-800 transition-colors flex items-center justify-center text-gray-400 relative" id="alert-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute top-2.5 right-3 w-2 h-2 bg-red-500 rounded-full border border-smm-card hidden" id="alert-dot"></span>
                    </button>
                    <!-- Search Trigger -->
                    <button class="w-10 h-10 rounded-full bg-smm-card hover:bg-gray-800 transition-colors flex items-center justify-center text-blue-400" id="open-search">
                        <svg viewBox="0 0 24 24" class="w-5 h-5 fill-none stroke-current stroke-2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </button>
                </div>
            </div>
            
        </header>

        <!-- Dynamic Information Bars (Moved Below Header) -->
        <div class="px-1 mb-2">
            <!-- Marquee -->
            <?php if ($marquee_enabled): ?>
            <div class="bg-smm-card rounded-lg py-1.5 px-3 text-sm text-gray-300 border border-gray-800 flex items-center gap-3 overflow-hidden shadow-lg mb-2">
                <span class="text-[10px] font-bold text-white bg-blue-600 px-2 py-0.5 rounded uppercase tracking-wider flex-shrink-0">News</span>
                <div class="marquee-container w-full h-5 relative flex items-center">
                     <span class="marquee-text whitespace-nowrap absolute">
                        <?php echo htmlspecialchars($marquee_text); ?> 
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Holiday Discount Banner -->
            <?php if ($discount_percent > 0 && $holiday_name): ?>
            <div class="bg-gradient-to-r from-red-600 to-pink-600 rounded-lg py-2 px-4 text-white border border-red-500 flex items-center gap-3 shadow-lg animate-pulse mb-3">
                <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z"/>
                </svg>
                <div class="flex-1">
                    <div class="font-bold text-sm">🎉 <?php echo htmlspecialchars($holiday_name); ?> - <?php echo $discount_percent; ?>% OFF!</div>
                    <div class="text-xs opacity-90">All services are discounted. Order now and save!</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order View -->
        <div id="order-view" class="tab-content">
        <!-- Social Media Grid -->
        <div class="social-grid grid grid-cols-3 gap-3 mb-4" id="social-grid">
            <!-- Top -->
            <button class="social-btn group" data-platform="top">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-7 h-7 text-yellow-400 fill-current">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span>Top</span>
                </div>
            </button>

            <!-- YouTube -->
            <button class="social-btn group" data-platform="youtube">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-7 h-7 text-red-600 fill-current">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span>YouTube</span>
                </div>
            </button>

            <!-- TikTok -->
            <button class="social-btn group" data-platform="tiktok">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-7 h-7 fill-current">
                        <defs>
                            <linearGradient id="tiktok-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#00f2ea"/>
                                <stop offset="100%" style="stop-color:#ff0050"/>
                            </linearGradient>
                        </defs>
                        <path fill="url(#tiktok-gradient)" d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                    </svg>
                    <span>TikTok</span>
                </div>
            </button>

            <!-- Telegram -->
            <button class="social-btn group" data-platform="telegram">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-7 h-7 text-blue-400 fill-current">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                    <span>Telegram</span>
                </div>
            </button>

            <!-- Facebook -->
            <button class="social-btn group" data-platform="facebook">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-7 h-7 text-blue-600 fill-current">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span>Facebook</span>
                </div>
            </button>

            <!-- Instagram -->
            <button class="social-btn group" data-platform="instagram">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-7 h-7">
                        <path fill="#E1306C" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    <span>Instagram</span>
                </div>
            </button>

            <!-- X -->
            <button class="social-btn group" data-platform="twitter">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-7 h-7 fill-current text-white">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    <span>Twitter/X</span>
                </div>
            </button>

            <!-- WhatsApp -->
            <button class="social-btn group" data-platform="whatsapp">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-7 h-7 text-green-500 fill-current">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    <span>WhatsApp</span>
                </div>
            </button>

            <!-- Other -->
            <button class="social-btn group" data-platform="other">
                <div class="relative w-full h-full flex items-center justify-center gap-2">
                    <svg viewBox="0 0 24 24" class="w-6 h-6 text-gray-400 fill-current">
                        <path d="M4 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm8-12a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm8-12a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                    </svg>
                    <span>Other</span>
                </div>
            </button>
        </div>

        <!-- Category Selection -->
        <div class="selection-card selection-card-clickable" id="category-card" data-action="open-category">
            <div class="selection-label">Category</div>
            <div class="selection-dropdown" id="category-dropdown">
                <span class="placeholder" id="category-text">Select a category</span>
                <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </div>
        </div>

        <!-- Service Selection -->
        <div class="selection-card selection-card-clickable" id="service-card" data-action="open-service">
            <div class="selection-label">Service</div>
            <div class="selection-dropdown" id="service-dropdown">
                <span class="placeholder" id="service-text">Select a service</span>
                <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </div>
            <div class="service-info" id="service-info" data-no-modal>
                <div class="service-info-row">
                    <span class="service-info-label">Min Order</span>
                    <span class="service-info-value" id="service-min">-</span>
                </div>
                <div class="service-info-row">
                    <span class="service-info-label">Max Order</span>
                    <span class="service-info-value" id="service-max">-</span>
                </div>
                <div class="service-info-row" id="avg-time-row">
                    <span class="service-info-label">Average Time</span>
                    <span class="service-info-value" id="service-average-time">-</span>
                </div>
                <div class="service-info-row">
                    <span class="service-info-label">Rate per 1000</span>
                    <span class="service-info-value" id="service-rate">-</span>
                </div>
            </div>
        </div>

        <!-- Order Form -->
        <div class="order-form">
            <div class="form-group">
                <label class="form-label" for="order-link">Link</label>
                <input type="url" class="form-input" id="order-link" placeholder="https://instagram.com/p/...">
            </div>
            
            <div class="form-group" id="quantity-group">
                <label class="form-label" for="order-quantity">Quantity</label>
                <input type="number" class="form-input" id="order-quantity" placeholder="Enter quantity (multiple of 10)">
            </div>
            
            <div class="form-group hidden" id="username-group">
                <label class="form-label" for="order-username">Username</label>
                <input type="text" class="form-input" id="order-username" placeholder="Enter username">
            </div>
            
            <div class="form-group hidden" id="answer-number-group">
                <label class="form-label" for="order-answer-number">Answer Number</label>
                <input type="text" class="form-input" id="order-answer-number" placeholder="Enter answer number (e.g. 1)">
            </div>

            <div class="form-group hidden" id="comments-group">
                <label class="form-label" for="order-comments">Comments (1 per line)</label>
                <textarea class="form-input" id="order-comments" rows="5" placeholder="Great post!&#10;Love this!&#10;Amazing!"></textarea>
            </div>
            
            <!-- Moved Charge Display Higher for Mobile Numpad Visibility -->
            <div class="charge-display mb-3">
                <span class="charge-label">Total Charge</span>
                <span class="charge-amount" id="charge-amount">0.00 ETB</span>
            </div>

            <?php if ($user_can_order): ?>
                <button class="order-btn" id="order-btn">Place Order</button>
            <?php else: ?>
                <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 text-center mb-4">
                    <div class="text-red-500 font-bold mb-1">⚠️ SYSTEM UNDER MAINTENANCE</div>
                    <div class="text-gray-400 text-xs text-balance">Orders are temporarily disabled for system updates. Please check back later.</div>
                </div>
                <button class="order-btn opacity-50 cursor-not-allowed" disabled>Ordering Disabled</button>
            <?php endif; ?>
            
            <!-- Compact bottom space -->
            <div class="h-4 md:hidden"></div>
        </div>
        </div> <!-- Close order-view -->



        <!-- History View (Hidden by default) -->
        <div id="history-view" class="tab-content" style="display: none;">
            <!-- Search Input (Hidden by default) -->
            <div id="history-search-container" class="search-container" style="display: none;">
                <input type="text" id="history-search-input" class="search-input" placeholder="Search by service ID...">
            </div>
            
            <div class="filter-chips-container">
                <div class="filter-chips">
                    <div class="filter-chip active" data-filter="all">All</div>
                    <div class="filter-chip" data-filter="pending">Pending</div>
                    <div class="filter-chip" data-filter="processing">Processing</div>
                    <div class="filter-chip" data-filter="completed">Completed</div>
                    <div class="filter-chip" data-filter="cancelled">Cancelled</div>
                </div>
                <div class="filter-actions">
                    <button class="filter-action-btn" id="toggle-history-search" title="Search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </button>
                    <button class="filter-action-btn" id="refresh-history" title="Refresh">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 2v6h-6"></path>
                            <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                            <path d="M3 22v-6h6"></path>
                            <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="tab-content-body" id="order-history-list">
                <!-- Populated by JS -->
                <div class="text-center text-gray-500 py-10">Loading orders...</div>
            </div>
        </div>

        <!-- Deposit View -->
        <div id="deposit-view" class="tab-content" style="display: none;">
           
            <div class="main-content-area pb-4 pl-4 pr-4">
                <div class="bg-smm-card p-6 rounded-xl border border-gray-800 shadow-lg mb-6 text-center max-w-md mx-auto mt-4">
                    <p class="text-gray-400 mb-2 font-medium">Current Balance</p>
                    <h2 class="text-4xl font-bold text-white mb-8 transition-all" id="deposit-balance-display">0.00 ETB</h2>

                    <!-- Amount Input -->
                    <div class="mb-4 text-left">
                        <label class="block text-gray-500 text-xs mb-1 uppercase font-bold tracking-wider" for="deposit-amount">Amount to Add</label>
                        <div class="relative">
                             <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 font-bold pointer-events-none z-10">ETB</span>
                             <input type="text" 
                                    inputmode="decimal" 
                                    pattern="[0-9]*" 
                                    id="deposit-amount" 
                                    autocomplete="off"
                                    autocorrect="off"
                                    autocapitalize="off"
                                    spellcheck="false"
                                    class="w-full bg-smm-input border border-gray-700 rounded-lg py-4 pl-14 pr-4 text-white text-xl font-bold focus:outline-none focus:border-smm-accent focus:ring-1 focus:ring-smm-accent transition-all placeholder-gray-700" 
                                    placeholder="0">
                        </div>
                    </div>

                    <!-- Preset Chips -->
                    <div class="grid grid-cols-4 gap-2 mb-8">
                        <button class="amount-chip py-2 px-1 bg-gray-800/80 border border-gray-700 hover:bg-gray-700 rounded-lg text-sm text-gray-300 font-medium transition-all active:scale-95" data-amount="10">+10</button>
                        <button class="amount-chip py-2 px-1 bg-gray-800/80 border border-gray-700 hover:bg-gray-700 rounded-lg text-sm text-gray-300 font-medium transition-all active:scale-95" data-amount="100">+100</button>
                        <button class="amount-chip py-2 px-1 bg-gray-800/80 border border-gray-700 hover:bg-gray-700 rounded-lg text-sm text-gray-300 font-medium transition-all active:scale-95" data-amount="1000">+1k</button>
                        <button class="amount-chip py-2 px-1 bg-gray-800/80 border border-gray-700 hover:bg-gray-700 rounded-lg text-sm text-gray-300 font-medium transition-all active:scale-95" data-amount="10000">+10k</button>
                    </div>

                  

                    <button id="deposit-btn" class="w-full bg-smm-accent hover:bg-opacity-90 text-white font-bold py-4 rounded-lg shadow-lg flex items-center justify-center gap-2 transition-all transform hover:scale-[1.02] hover:shadow-xl active:scale-95">
                        <span class="text-lg">Deposit</span>
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2-2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </button>
                    <!-- Chapa Container - Transparent Wrapper -->
                    <div id="chapa-container-wrapper" class="hidden mt-6 overflow-hidden transition-all">
                        <div id="chapa-inline-form" class="payment-container" style="height: auto; width: 100%;"></div>
                        <!-- Error Container -->
                        <div id="chapa-error-container" class="hidden mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm font-medium text-left"></div>
                    </div>
                    
                    <div class="flex items-center justify-center gap-2 mt-6 opacity-30">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                        <p class="text-[10px] text-white uppercase tracking-widest">Secured by Chapa</p>
                    </div>
                </div>
                
                <!-- History Preview (Optional) -->
                <!-- History Preview -->
                <div class="mt-8">
                    <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-3 px-1">Recent Deposits</h3>
                    <div id="recent-deposits-list" class="space-y-2">
                         <!-- Populated by JS -->
                         <div class="text-center text-gray-600 text-sm py-4">Loading history...</div>
                    </div>
                </div>
            </div>
        </div>



    <!-- Refer / More Tab -->

    <div id="refer-view" class="tab-content" style="display: none;">
        <div class="main-content-area p-4 pb-24">
            
            <!-- Theme Toggle -->
            <div class="flex justify-between items-center mb-6 bg-smm-card p-4 rounded-xl border border-gray-800">
                <div class="flex items-center gap-3">
                     <div class="w-10 h-10 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                     </div>
                     <div>
                        <h3 class="font-bold text-white text-base">App Theme</h3>
                        <p class="text-gray-400 text-xs">Switch to light/dark mode</p>
                    </div>
                </div>
                <label class="theme-toggle">
                    <input type="checkbox" id="theme-switch">
                    <span class="theme-toggle-slider">
                        <svg class="moon-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg class="sun-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" clip-rule="evenodd"></path></svg>
                    </span>
                </label>
            </div>

            <?php if (isset($auth_provider) && $auth_provider === 'google'): ?>
            <!-- Account Section (Google Users) -->
            <div class="flex justify-between items-center mb-6 bg-smm-card p-4 rounded-xl border border-gray-800">
                <div class="flex items-center gap-3">
                     <div class="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center text-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                     </div>
                     <div>
                        <h3 class="font-bold text-white text-base">Sign Out</h3>
                        <p class="text-gray-400 text-xs">Logged in with Google</p>
                    </div>
                </div>
                <a href="logout.php" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg text-sm font-medium transition-colors border border-red-500/20">
                    Logout
                </a>
            </div>
            <?php endif; ?>
            <!-- Referral Coming Soon -->
            <div class="refer-card mb-6 text-center py-8 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-yellow-500/5 to-orange-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                
                <div class="w-16 h-16 bg-gradient-to-br from-yellow-500/20 to-orange-600/20 rounded-2xl flex items-center justify-center mx-auto mb-4 text-yellow-500 border border-yellow-500/20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-theme-primary mb-2">Refer & Earn</h3>
                <div class="inline-block rounded-full px-3 py-1 text-[10px] font-bold text-white bg-gradient-to-r from-yellow-600 to-orange-600 mb-3 shadow-lg shadow-orange-500/20">COMING SOON</div>
                <p class="text-gray-400 text-sm px-4 leading-relaxed">An exciting new way to earn rewards by inviting friends is under development.</p>
            </div>

            <!-- Live Chat -->
            <div class="bg-smm-card rounded-xl border border-gray-800 p-0 mb-6 relative overflow-hidden flex flex-col">
                 <div class="flex items-center justify-between p-4 border-b border-gray-800 bg-white/5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center text-green-500 relative">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                             <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-smm-card rounded-full"></span>
                        </div>
                        <div>
                            <h3 class="font-bold text-theme-primary text-base">Live Support</h3>
                            <div class="flex items-center gap-1.5 text-[10px] text-green-500 font-bold uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Online
                            </div>
                        </div>
                    </div>
                 </div>
                 
                 <div class="chat-messages-container h-64 overflow-y-auto p-4 space-y-3 bg-black/20" id="chat-messages">
                     <div class="text-center text-gray-500 text-xs py-10 opacity-70">
                         <p>Welcome to Paxyo Support!</p>
                         <p class="mt-1">Messages are secure and private.</p>
                     </div>
                 </div>
                 
                 <div class="p-3 bg-smm-card border-t border-gray-800">
                     <form id="chat-form" class="flex gap-2">
                         <input type="text" id="chat-input" class="flex-1 bg-smm-input border border-gray-700 rounded-full px-4 py-2.5 text-sm text-white focus:outline-none focus:border-smm-accent transition-colors placeholder-gray-600" placeholder="Type a message...">
                         <button type="submit" id="send-chat-btn" class="w-10 h-10 rounded-full bg-smm-accent text-white flex items-center justify-center hover:bg-opacity-90 transition-all transform active:scale-95 shadow-lg shadow-indigo-500/30">
                             <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                         </button>
                     </form>
                 </div>
            </div>

            <!-- About Us -->
            <div class="bg-smm-card rounded-xl border border-gray-800 p-5 mb-6">
                <h3 class="font-bold text-theme-primary text-lg mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </span>
                    About Us
                </h3>
                <div class="space-y-3">
                    <p class="text-gray-300 text-sm leading-relaxed">
                        Paxyo gives you easy-to-use tools to grow your social media.
                        Our smart engagement tools help your content reach more people and get noticed.
                    </p>
                    <div class="p-3 bg-white/5 rounded-lg border-l-2 border-smm-accent">
                         <p class="text-gray-200 text-sm italic font-medium leading-relaxed">
                            "Fast, simple, and reliable - Paxyo makes growing online easy."
                        </p>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="bg-smm-card rounded-xl border border-gray-800 p-5">
                <h3 class="font-bold text-theme-primary text-lg mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </span>
                    Contact
                </h3>
                
                <div class="space-y-3">
                    <a href="mailto:paxyo251@gmail.com" class="flex items-center gap-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors border border-transparent hover:border-gray-700">
                        <div class="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center text-red-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Email</div>
                            <div class="text-sm text-theme-primary font-medium">paxyo251@gmail.com</div>
                        </div>
                    </a>
                    
                    <a href="mailto:Info@paxyo.com" class="flex items-center gap-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors border border-transparent hover:border-gray-700">
                        <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                             <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Info</div>
                            <div class="text-sm text-theme-primary font-medium">Info@paxyo.com</div>
                        </div>
                    </a>

                    <a href="tel:0993960702" class="flex items-center gap-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors border border-transparent hover:border-gray-700">
                        <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center text-green-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        </div>
                        <div>
                             <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Phone</div>
                            <div class="text-sm text-theme-primary font-medium">0993960702</div>
                        </div>
                    </a>
                    
                    <a href="https://t.me/Paxyo" target="_blank" class="flex items-center gap-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors border border-transparent hover:border-gray-700">
                        <div class="w-10 h-10 rounded-full bg-blue-400/10 flex items-center justify-center text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </div>
                        <div>
                             <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Telegram</div>
                            <div class="text-sm text-theme-primary font-medium">@Paxyo</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>


    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <button class="nav-item active" data-tab="order">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <span>Order</span>
        </button>
        <button class="nav-item" data-tab="history">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12,6 12,12 16,14"/>
            </svg>
            <span>History</span>
        </button>
        <button class="nav-item" data-tab="deposit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <span>Deposit</span>
        </button>
        <button class="nav-item" data-tab="refer">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="1"/>
                <circle cx="19" cy="12" r="1"/>
                <circle cx="5" cy="12" r="1"/>
            </svg>
            <span>More</span>
        </button>

    </nav>

    <!-- Alert Modal -->
    <div class="search-modal" id="alert-modal">
        <div class="search-header">
            <button class="search-back" id="close-alert">
                 <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </button>
            <div class="font-bold text-lg text-white ml-2">Notifications</div>
        </div>
        <div class="search-results" id="alert-list">
            <!-- Populated by JS -->
        </div>
    </div>

    <!-- Search Modal -->
    <div class="search-modal" id="search-modal">
        <div class="search-header">
            <button class="search-back" id="close-search">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </button>
            <div class="search-input-wrapper">
                <input type="text" class="search-input" id="search-input" placeholder="Search services...">
                <svg class="search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
            </div>
        </div>
        <div class="search-results" id="search-results">
            <!-- Results populated by JS -->
        </div>
    </div>

    <!-- Category Dropdown Modal -->
    <div class="dropdown-modal" id="category-modal">
        <div class="dropdown-content">
            <div class="dropdown-header">
                <span class="dropdown-title" id="category-modal-title">Select Category</span>
                <div class="modal-search">
                    <div class="modal-search-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>
                    <input type="text" class="modal-search-input" id="category-search-input" placeholder="Search categories...">
                    <button class="modal-search-clear hidden" id="category-search-clear">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="modal-search-toggle-btn" id="category-search-toggle"></div>
                </div>
                <button class="dropdown-close" id="close-category-modal">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="dropdown-list" id="category-list">
                <div class="dropdown-empty-state" id="category-empty-state">No categories found</div>
                <!-- Categories populated by JS -->
            </div>
        </div>
    </div>

    <!-- Service Dropdown Modal -->
    <div class="dropdown-modal" id="service-modal">
        <div class="dropdown-content">
            <div class="dropdown-header">
                <span class="dropdown-title" id="service-modal-title">Select Service</span>
                <div class="modal-search">
                    <div class="modal-search-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>
                    <input type="text" class="modal-search-input" id="service-search-input" placeholder="Search services...">
                    <button class="modal-search-clear hidden" id="service-search-clear">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="modal-search-toggle-btn" id="service-search-toggle"></div>
                </div>
                <button class="dropdown-close" id="close-service-modal">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="dropdown-list" id="service-list">
                <div class="dropdown-empty-state" id="service-empty-state">No services found</div>
                <!-- Services populated by JS -->
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span class="toast-message" id="toast-message">Message</span>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
    // =============================================
    // SMM Platform JavaScript
    // =============================================
    
    const SMM = {
        // State
        services: [],
        categories: [],
        orders: [],
        selectedPlatform: null,
        selectedCategory: null,
        selectedService: null,
        recommendedServices: [],
        alerts: [],
        hiddenServices: new Set(<?php echo json_encode($hidden_services); ?>),
        userBalance: <?php echo json_encode((float)$balance); ?>,
        rateMultiplier: <?php echo json_encode((float)$rate_multiplier); ?>,
        discountPercent: <?php echo json_encode((float)$discount_percent); ?>,
        holidayName: <?php echo json_encode($holiday_name); ?>,
        hasVisited: localStorage.getItem('smm_visited') === 'true',
        
        // Telegram Mini App state
        isTelegramApp: !!(window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.initData),
        telegramUser: null,
        userId: <?php echo json_encode($user_id); ?>,
        userName: <?php echo json_encode($first_name); ?>,
        userPhoto: <?php echo json_encode($user_profile_img); ?>,
        userCanOrder: <?php echo $user_can_order ? 'true' : 'false'; ?>,
        
        // DOM Elements
        elements: {},

        // Flexible ETB Formatter
        formatETB(amount) {
            const val = parseFloat(amount || 0);
            if (val === 0) return '0.00 ETB';
            
            // For very small amounts, show more decimals
            if (val < 0.1) {
                return val.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 6
                }) + ' ETB';
            }
            
            // Standard format
            return val.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' ETB';
        },

        // Helper for raw value formatting without suffix
        formatValue(amount, maxDecimals = 6) {
            const val = parseFloat(amount || 0);
            if (val === 0) return '0.00';
            return val.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: val < 0.1 ? maxDecimals : 2
            });
        },
        
        // Initialize - with Telegram authentication
        async init() {
            // Safety: scroll wrapper reference
            const scrollWrapper = document.getElementById('tg-scroll-wrapper');
            const forceScroll = () => {
                // Re-assert Telegram vertical swipe disable
                if (window.Telegram && window.Telegram.WebApp) {
                    const tg = window.Telegram.WebApp;
                    if (tg.disableVerticalSwipes) tg.disableVerticalSwipes();
                    try { tg.isVerticalSwipesEnabled = false; } catch(e) {}
                }
            };
            forceScroll();

            // CRITICAL: Prevent Telegram from collapsing the app when scrolled to top
            // When scrollTop === 0, Telegram intercepts the swipe-down as a close gesture.
            // By nudging scrollTop to 1px, we keep it in "scrolling" mode.
            if (scrollWrapper) {
                scrollWrapper.addEventListener('touchstart', () => {
                    if (scrollWrapper.scrollTop === 0) {
                        scrollWrapper.scrollTop = 1;
                    }
                }, { passive: true });
                // Also set initial scrollTop to 1 to avoid the 0 trap
                scrollWrapper.scrollTop = 1;
            }

            // Safety timeout: ensure loader hides even if something hangs
            const loaderTimeout = setTimeout(() => {
                const loadingEl = document.getElementById('tg-loading');
                if (loadingEl) {
                    loadingEl.classList.add('hidden');
                    forceScroll();
                }
                console.warn('⚠️ Force-hid loader due to timeout');
            }, 2500);

            try {
                // Authenticate with Telegram first if in Mini App
                if (this.isTelegramApp) {
                    await Promise.race([
                        this.authenticateTelegram(),
                        new Promise(resolve => setTimeout(resolve, 1500)) 
                    ]);
                }
                
                // Hide loading overlay immediately after auth attempt
                const loadingEl = document.getElementById('tg-loading');
                if (loadingEl) loadingEl.classList.add('hidden');
                clearTimeout(loaderTimeout);
                forceScroll();
                
                this.cacheElements();
                this.bindEvents();
                this.loadServices();
                this.initDeposit();
                this.checkDepositReturn();
                this.setupTelegramBackButton();
                this.startHeartbeat();

                // Final safety check after a short wait for layout
                setTimeout(forceScroll, 500);
            } catch (error) {
                console.error('Init failed:', error);
                const loadingEl = document.getElementById('tg-loading');
                if (loadingEl) loadingEl.classList.add('hidden');
                forceScroll();
            }
        },
        
        // Heartbeat ping for real-time online status (every 30 seconds)
        startHeartbeat() {
            // Initial ping
            fetch('heartbeat.php').catch(() => {});
            
            // Ping every 30 seconds
            setInterval(() => {
                fetch('heartbeat.php').catch(() => {});
            }, 30000);
        },
        
        // Telegram Mini App Authentication
        async authenticateTelegram() {
            if (!this.isTelegramApp) {
                console.log('📱 Not running as Telegram Mini App - using session auth');
                return;
            }
            
            const tg = window.Telegram.WebApp;
            
            try {
                console.log('🔐 Authenticating with Telegram initData...');
                
                const response = await fetch('telegram_auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ initData: tg.initData })
                });
                
                const data = await response.json();
                
                if (data.success && data.user) {
                    this.telegramUser = data.user;
                    this.userId = data.user.id;
                    this.userBalance = data.user.balance || 0;
                    
                    // Build display name - Prioritize first_name as requested
                    this.userName = data.user.display_name || data.user.first_name || (data.user.username ? '@' + data.user.username : 'User ' + String(this.userId).slice(-4));
                    
                    // Update photo URL if available
                    if (data.user.photo_url) {
                        this.userPhoto = data.user.photo_url;
                    }
                    
                    // Update UI with Telegram user data
                    this.updateUserDisplay();
                    
                    console.log('✅ Telegram user authenticated:', this.userName, 'ID:', this.userId);
                } else {
                    console.warn('⚠️ Telegram auth failed:', data.error || 'Unknown error');
                    // Fall back to initDataUnsafe for display (less secure but works for UI)
                    if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                        const user = tg.initDataUnsafe.user;
                        this.userId = user.id;
                        this.userName = user.first_name || (user.username ? '@' + user.username : 'User ' + String(user.id).slice(-4));
                        if (user.photo_url) this.userPhoto = user.photo_url;
                        this.updateUserDisplay();
                    }
                }
            } catch (error) {
                console.error('❌ Telegram auth error:', error);
                // Still try to use initDataUnsafe for UI
                if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                    const user = tg.initDataUnsafe.user;
                    this.userId = user.id;
                    this.userName = user.first_name || (user.username ? '@' + user.username : 'User ' + String(user.id).slice(-4));
                    this.updateUserDisplay();
                }
            }
        },
        
        // Update user display after Telegram auth
        updateUserDisplay() {
            // Update header profile
            const profileImg = document.querySelector('.global-header img');
            const profileName = document.querySelector('.global-header h1');
            const balanceDisplay = document.getElementById('user-balance');
            
            if (profileImg && this.userPhoto) {
                profileImg.src = this.userPhoto;
            }
            if (profileName) {
                profileName.textContent = this.userName;
            }
            if (balanceDisplay) {
                balanceDisplay.textContent = this.formatETB(this.userBalance);
            }
        },
        
        // Telegram back button handling
        setupTelegramBackButton() {
            if (!this.isTelegramApp) return;
            
            const tg = window.Telegram.WebApp;
            
            // Listen for back button presses
            tg.BackButton.onClick(() => {
                // Check if any modal is open
                const modals = ['search-modal', 'category-modal', 'service-modal', 'alert-modal'];
                for (const modalId of modals) {
                    const modal = document.getElementById(modalId);
                    if (modal && modal.classList.contains('visible')) {
                        modal.classList.remove('visible');
                        if (modals.some(m => document.getElementById(m)?.classList.contains('visible'))) {
                            // Still have open modals
                        } else {
                            tg.BackButton.hide();
                        }
                        return;
                    }
                }
                
                // Check if on non-order tab
                const historyView = document.getElementById('history-view');
                const depositView = document.getElementById('deposit-view');
                if (historyView?.style.display !== 'none' || depositView?.style.display !== 'none') {
                    this.switchTab('order');
                    tg.BackButton.hide();
                    return;
                }
                
                // No modals, on order tab - close the app
                tg.close();
            });
        },
        
        // Show Telegram back button when entering modals/tabs
        showTelegramBackButton() {
            if (this.isTelegramApp) {
                window.Telegram.WebApp.BackButton.show();
            }
        },
        
        hideTelegramBackButton() {
            if (this.isTelegramApp) {
                window.Telegram.WebApp.BackButton.hide();
            }
        },
        
        // Cache DOM elements
        cacheElements() {
            this.elements = {
                socialGrid: document.getElementById('social-grid'),
                categoryDropdown: document.getElementById('category-dropdown'),
                categoryText: document.getElementById('category-text'),
                serviceDropdown: document.getElementById('service-dropdown'),
                serviceText: document.getElementById('service-text'),
                serviceInfo: document.getElementById('service-info'),
                serviceMin: document.getElementById('service-min'),
                serviceMax: document.getElementById('service-max'),
                serviceAverageTime: document.getElementById('service-average-time'),
                avgTimeRow: document.getElementById('avg-time-row'),
                serviceRate: document.getElementById('service-rate'),
                orderLink: document.getElementById('order-link'),
                orderQuantity: document.getElementById('order-quantity'),
                chargeAmount: document.getElementById('charge-amount'),
                orderBtn: document.getElementById('order-btn'),
                searchModal: document.getElementById('search-modal'),
                searchInput: document.getElementById('search-input'),
                searchResults: document.getElementById('search-results'),
                categoryModal: document.getElementById('category-modal'),
                categoryList: document.getElementById('category-list'),
                serviceModal: document.getElementById('service-modal'),
                serviceList: document.getElementById('service-list'),
                toast: document.getElementById('toast'),
                toastMessage: document.getElementById('toast-message'),
                loadingOverlay: document.getElementById('loading-overlay'),
                alertBtn: document.getElementById('alert-btn'),
                alertDot: document.getElementById('alert-dot'),
                alertModal: document.getElementById('alert-modal'),
                alertList: document.getElementById('alert-list'),
                closeAlert: document.getElementById('close-alert'),
                
                quantityGroup: document.getElementById('quantity-group'),
                commentsGroup: document.getElementById('comments-group'),
                orderComments: document.getElementById('order-comments'),
                usernameGroup: document.getElementById('username-group'),
                orderUsername: document.getElementById('order-username'),
                answerNumberGroup: document.getElementById('answer-number-group'),
                orderAnswerNumber: document.getElementById('order-answer-number')
            };
        },
        
        // Bind events
        bindEvents() {
            // Social platform buttons - optimized with delegation
            this.elements.socialGrid.addEventListener('click', (e) => {
                const btn = e.target.closest('.social-btn');
                if (btn) {
                    // Haptic feedback for mobile
                    if (this.isTelegramApp && window.Telegram?.WebApp?.HapticFeedback) {
                        window.Telegram.WebApp.HapticFeedback.impactOccurred('light');
                    }
                    this.selectPlatform(btn.dataset.platform);
                }
            });
            
            // Category card - entire card is clickable
            const categoryCard = document.getElementById('category-card');
            categoryCard.addEventListener('click', (e) => {
                // Skip if clicking on an excluded element
                if (e.target.closest('[data-no-modal]')) return;
                
                if (!this.selectedPlatform) {
                    this.showToast('Please select a social media platform first', 'error');
                    // Highlight the social grid
                    this.elements.socialGrid.classList.add('pulse-hint');
                    setTimeout(() => this.elements.socialGrid.classList.remove('pulse-hint'), 600);
                    return;
                }
                if (this.isTelegramApp && window.Telegram?.WebApp?.HapticFeedback) {
                    window.Telegram.WebApp.HapticFeedback.impactOccurred('light');
                }
                this.showCategoryModal();
            });
            
            // Service card - entire card is clickable
            const serviceCard = document.getElementById('service-card');
            serviceCard.addEventListener('click', (e) => {
                // Skip if clicking on service-info (data-no-modal) or its children
                if (e.target.closest('[data-no-modal]')) return;
                
                if (!this.selectedCategory) {
                    if (!this.selectedPlatform) {
                        this.showToast('Please select a social media platform first', 'error');
                        this.elements.socialGrid.classList.add('pulse-hint');
                        setTimeout(() => this.elements.socialGrid.classList.remove('pulse-hint'), 600);
                    } else {
                        this.showToast('Please select a category first', 'error');
                        categoryCard.classList.add('pulse-hint');
                        setTimeout(() => categoryCard.classList.remove('pulse-hint'), 600);
                    }
                    return;
                }
                if (this.isTelegramApp && window.Telegram?.WebApp?.HapticFeedback) {
                    window.Telegram.WebApp.HapticFeedback.impactOccurred('light');
                }
                this.showServiceModal();
            });

            // Remove highlight on interaction
            this.elements.orderLink.addEventListener('focus', () => {
                this.elements.orderLink.classList.remove('input-highlight');
            });
            categoryCard.addEventListener('click', () => {
                document.querySelector('#category-dropdown .arrow')?.classList.remove('arrow-highlight');
            });
            serviceCard.addEventListener('click', () => {
                document.querySelector('#service-dropdown .arrow')?.classList.remove('arrow-highlight');
            });
            
            // Quantity input - real-time calculation
            this.elements.orderQuantity.addEventListener('input', () => {
                this.calculateCharge();
            });
            this.elements.orderComments.addEventListener('input', () => {
                this.calculateCharge();
            });
            
            // Order button
            this.elements.orderBtn.addEventListener('click', () => {
                this.placeOrder();
            });
            
            // Search modal
            document.getElementById('open-search').addEventListener('click', () => {
                // Switch to order tab if not already there
                const orderView = document.getElementById('order-view');
                if (orderView.style.display === 'none') {
                    this.switchTab('order');
                }
                this.openSearchModal();
            });
            document.getElementById('close-search').addEventListener('click', () => {
                this.closeSearchModal();
            });
            this.elements.searchInput.addEventListener('input', (e) => {
                this.searchServices(e.target.value);
            });
            
            // Alert Modal
            this.elements.alertBtn.addEventListener('click', () => this.showAlertModal());
            this.elements.closeAlert.addEventListener('click', () => this.elements.alertModal.classList.remove('visible'));
            
            // Category modal close
            document.getElementById('close-category-modal').addEventListener('click', () => {
                this.elements.categoryModal.classList.remove('visible');
            });
            this.elements.categoryModal.addEventListener('click', (e) => {
                if (e.target === this.elements.categoryModal) {
                    this.elements.categoryModal.classList.remove('visible');
                }
            });
            
            // Service modal close
            document.getElementById('close-service-modal').addEventListener('click', () => {
                this.elements.serviceModal.classList.remove('visible');
            });
            this.elements.serviceModal.addEventListener('click', (e) => {
                if (e.target === this.elements.serviceModal) {
                    this.elements.serviceModal.classList.remove('visible');
                }
            });
            
            // Bottom navigation
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', () => {
                    this.switchTab(item.dataset.tab);
                });
            });
            
            // History events
            this.initHistory();
            
            // Bind expandable search events
            this.bindSearchExpandableEvents();
            
            // Start Unified Real-time Stream (handles orders, alerts, balance)
            this.initRealtimeUpdates();
            
            // Fetch initial alerts state
            this.fetchAlerts();
        },
        
        async fetchAlerts() {
            try {
                const res = await fetch('get_alerts.php');
                const data = await res.json();
                
                if (data.alerts) {
                    if (data.unread_count > 0) {
                        this.elements.alertDot.classList.remove('hidden');
                    } else {
                        this.elements.alertDot.classList.add('hidden');
                    }
                }
            } catch (e) {
                console.error("Failed to fetch alerts", e);
            }
        },

        handleAlertsUpdate(alerts, unread_count) {
            this.alerts = alerts;
            if (unread_count > 0) {
                this.elements.alertDot.classList.remove('hidden');
            } else {
                this.elements.alertDot.classList.add('hidden');
            }
            // If modal is open, refresh its content
            if (this.elements.alertModal.classList.contains('visible')) {
                this.renderAlerts();
            }
        },
        
        renderAlerts() {
            const alerts = this.alerts || [];
            this.elements.alertList.innerHTML = alerts.map(alert => `
                <div class="py-3 border-b border-gray-800 last:border-0 relative">
                    ${alert.is_read == 0 ? '<span class="absolute right-0 top-3 w-1.5 h-1.5 bg-blue-500 rounded-full"></span>' : ''}
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-sm font-bold text-white">Admin Update</span>
                        <span class="text-[10px] text-gray-500">${new Date(alert.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                    <p class="text-gray-300 text-sm">${alert.message}</p>
                </div>
            `).join('') || '<div class="text-gray-500 text-center py-4">No notifications</div>';
        },

        // --- Unified Real-time Updates (SSE - No API Polling) ---
        initRealtimeUpdates() {
            if (this.eventSource) {
                this.eventSource.close();
            }
            
            console.log('🔴 Starting real-time stream (SSE)');
            this.eventSource = new EventSource('realtime_stream.php');
            
            this.eventSource.onopen = () => {
                console.log('✅ Real-time connection established');
            };
            
            this.eventSource.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                if (data.type === 'update') {
                    console.log('📡 Update received:', data.changes);
                    
                    // Handle orders update
                    if (data.changes.includes('orders')) {
                        this.handleOrdersUpdate(data.data.orders);
                    }
                    
                    // Handle alerts update
                    if (data.changes.includes('alerts')) {
                        this.handleAlertsUpdate(data.data.alerts, data.data.unread_count);
                    }
                    
                    // Handle balance update
                    if (data.changes.includes('balance')) {
                        this.updateBalance(data.data.balance);
                    }
                    
                    // Handle maintenance update
                    if (data.changes.includes('maintenance')) {
                        this.handleMaintenanceUpdate(data.data.maintenance);
                    }
                } else if (data.type === 'heartbeat') {
                    // Connection alive, no changes
                    console.log('💓 Heartbeat');
                }
            };
            
            this.eventSource.onerror = (error) => {
                console.log('❌ Connection lost, reconnecting in 3s...');
                this.eventSource.close();
                setTimeout(() => this.initRealtimeUpdates(), 3000);
            };
        },
        
        handleOrdersUpdate(orders) {
            const oldOrders = this.orders || [];
            this.orders = orders;
            
            // Re-render if history tab is visible
            if (document.getElementById('history-view').classList.contains('visible')) {
                this.renderHistory();
            }
            
            // Detect status changes and show notifications
            orders.forEach(order => {
                const oldOrder = oldOrders.find(o => o.id === order.id);
                if (oldOrder && oldOrder.status !== order.status) {
                    // Status changed!
                    if (order.status === 'canceled' || order.status === 'cancelled') {
                        this.showToast(`Order #${order.id} cancelled. Refund applied to your balance.`, 'success');
                    } else if (order.status === 'partial') {
                        this.showToast(`Order #${order.id} partially completed. Partial refund applied.`, 'info');
                    } else if (order.status === 'completed') {
                        this.showToast(`Order #${order.id} completed successfully! 🎉`, 'success');
                    } else if (order.status === 'processing') {
                        this.showToast(`Order #${order.id} is now processing...`, 'info');
                    }
                }
            });
        },
        
        handleAlertsUpdate(alerts, unread_count) {
            this.alerts = alerts;
            
            // Update notification dot
            if (unread_count > 0) {
                this.elements.alertDot.classList.remove('hidden');
            } else {
                this.elements.alertDot.classList.add('hidden');
            }
            
            // If alert modal is open, refresh it
            if (this.elements.alertModal.classList.contains('visible')) {
                this.showAlertModal();
            }
        },
        
        updateBalance(newBalance) {
            // Animate only if changed
            if (this.userBalance !== newBalance) {
                this.userBalance = newBalance;
                const el = document.getElementById('user-balance');
                el.textContent = this.formatETB(newBalance);
                el.classList.add('pulse'); // CSS animation
                setTimeout(() => el.classList.remove('pulse'), 2000);
            }
        },

        handleMaintenanceUpdate(maintenance) {
            const isMaintenance = maintenance.mode === '1';
            const allowedIds = (maintenance.allowed_ids || '').split(',').map(id => id.trim());
            const userCanOrder = isMaintenance ? allowedIds.includes(String(this.userId)) : true;
            
            // If the ordering status changed, reload to update the UI
            if (this.userCanOrder !== userCanOrder) {
                console.log('🔄 Maintenance status changed. Reloading UI...');
                location.reload();
            }
        },

        // --- Deposit Logic ---
        initDeposit() {
            const amountInput = document.getElementById('deposit-amount');
            const depositBtn = document.getElementById('deposit-btn');
            const chips = document.querySelectorAll('.amount-chip');
            
            // Chips handling
            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    const val = parseInt(chip.dataset.amount);
                    let current = parseInt(amountInput.value);
                    if (isNaN(current)) current = 0;
                    amountInput.value = current + val;
                    
                    // Visual feedback
                    chip.classList.add('bg-smm-accent', 'text-white', 'border-transparent');
                    setTimeout(() => chip.classList.remove('bg-smm-accent', 'text-white', 'border-transparent'), 200);
                    
                    // Reset Chapa wrapper if it's open so new amount is used
                    const chapaWrapper = document.getElementById('chapa-container-wrapper');
                    const depositBtn = document.getElementById('deposit-btn');
                    if (!chapaWrapper.classList.contains('hidden')) {
                        chapaWrapper.classList.add('hidden');
                        depositBtn.classList.remove('hidden');
                        document.getElementById('chapa-inline-form').innerHTML = '';
                    }
                    
                    amountInput.focus();
                });
            });
            
            // Deposit Button
            if (depositBtn) {
                depositBtn.addEventListener('click', () => {
                    const amount = parseFloat(amountInput.value);
                    if (!amount || amount < 10) {
                        this.showToast('Minimum deposit is 10 ETB', 'error');
                        return;
                    }
                    this.startChapaPayment(amount);
                });
            }
            
            // Reset if user changes amount while Chapa is open
            if (amountInput) {
                amountInput.addEventListener('input', () => {
                    const chapaWrapper = document.getElementById('chapa-container-wrapper');
                    const depositBtn = document.getElementById('deposit-btn');
                    
                    if (!chapaWrapper.classList.contains('hidden')) {
                        chapaWrapper.classList.add('hidden');
                        depositBtn.classList.remove('hidden');
                        document.getElementById('chapa-inline-form').innerHTML = '';
                    }
                });
            }
        },
        
        startChapaPayment(amount) {
            // Check if ChapaCheckout SDK is loaded
            if (typeof ChapaCheckout === 'undefined') {
                this.showToast('Payment system loading... please wait a moment.', 'info');
                setTimeout(() => this.startChapaPayment(amount), 1000);
                return;
            }
            
            this.showLoading(true);
            const depositBtn = document.getElementById('deposit-btn');
            const chapaWrapper = document.getElementById('chapa-container-wrapper');
            const chapaInline = document.getElementById('chapa-inline-form');
            const errorContainer = document.getElementById('chapa-error-container');
            
            // UI State Change
            depositBtn.classList.add('hidden');
            chapaWrapper.classList.remove('hidden');
            chapaWrapper.classList.remove('user-has-submitted'); // Reset
            if (errorContainer) errorContainer.classList.add('hidden');
            
            // Clear previous form
            chapaInline.innerHTML = '';
            
            // Track if payment was submitted to differentiate input errors from real errors
            let paymentSubmitted = false;
            
            // PHONE NUMBER AUTO-FORMATTER & VALIDATOR
            // M-Pesa (Safaricom): 07XXXXXXXX
            // Telebirr/CBEBirr/etc (Ethio Telecom): 09XXXXXXXX
            setTimeout(() => {
                const phoneInput = chapaInline.querySelector('input[type="tel"], input[name*="phone"], input[placeholder*="phone"], input[placeholder*="Phone"]');
                if (phoneInput) {
                    console.log('📱 Found Chapa phone input, adding smart validator');
                    
                    // Create validation message element
                    let validationMsg = document.createElement('div');
                    validationMsg.id = 'phone-validation-msg';
                    validationMsg.style.cssText = `
                        font-size: 13px;
                        font-weight: 500;
                        padding: 10px 14px;
                        margin: 12px 0;
                        border-radius: 8px;
                        display: none;
                        transition: all 0.2s ease;
                        text-align: center;
                        width: 100%;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                    `;

                    const getChapaButton = () => {
                        // Find the green "Pay Now" button in Chapa's form
                        return chapaInline.querySelector('button[type="submit"], button:last-of-type:not([class*="method"])');
                    };

                    // Insert the message above the button
                    const chapaBtn = getChapaButton();
                    if (chapaBtn) {
                        chapaBtn.parentNode.insertBefore(validationMsg, chapaBtn);
                    } else {
                        // Fallback to after phone input if button not found yet
                        phoneInput.parentNode.insertBefore(validationMsg, phoneInput.nextSibling);
                    }

                    const updateButtonState = (enabled) => {
                        const btn = getChapaButton();
                        if (btn) {
                            btn.disabled = !enabled;
                            btn.style.opacity = enabled ? '1' : '0.5';
                            btn.style.cursor = enabled ? 'pointer' : 'not-allowed';
                        }
                    };

                    const showValidation = (type, message) => {
                        // Dynamically move above button if Chapa finished rendering
                        const chapaBtn = getChapaButton();
                        if (chapaBtn && validationMsg.nextSibling !== chapaBtn) {
                            chapaBtn.parentNode.insertBefore(validationMsg, chapaBtn);
                        }

                        // Force clear if everything is OK
                        if (type === 'success' || type === 'hint') {
                             validationMsg.style.display = 'none';
                             validationMsg.innerHTML = '';
                        } else if (type === 'error') {
                            validationMsg.style.display = 'block';
                            validationMsg.style.background = 'rgba(255, 71, 87, 0.08)'; // Lighter background
                            validationMsg.style.color = '#ff4757';
                            validationMsg.style.border = '1px solid rgba(255, 71, 87, 0.15)';
                            validationMsg.style.padding = '10px 14px';
                            validationMsg.style.fontSize = '12px';
                            validationMsg.style.textAlign = 'center';
                            validationMsg.innerHTML = message; // Removed "Correction needed" header
                        }
                        
                        // Disable button unless it's a success message
                        updateButtonState(type === 'success');
                    };
                    
                    const hideValidation = () => {
                        validationMsg.style.display = 'none';
                        updateButtonState(false); // Keep disabled if no validation state
                    };
                    
                    // Initial state - prompt user immediately
                    setTimeout(() => {
                        validatePhone(phoneInput.value);
                    }, 1500); // Slightly longer delay to ensure full Chapa render
                    
                    const getSelectedPaymentMethod = () => {
                        // Find the element with active/selected classes OR the glow class we added
                        const activeEl = chapaInline.querySelector('[class*="active"], [class*="selected"], [aria-selected="true"], .payment-method.active');
                        
                        let methodName = '';
                        if (activeEl) {
                            // Combine text content and any image alt text for better identification
                            methodName = (activeEl.textContent || activeEl.innerText || '').toLowerCase();
                            const img = activeEl.querySelector('img');
                            if (img && img.alt) methodName += ' ' + img.alt.toLowerCase();
                        }
                        
                        // Detect M-Pesa (look for mpesa, m-pesa, or safaricom)
                        if (methodName.includes('mpesa') || methodName.includes('m-pesa') || methodName.includes('safaricom')) {
                            return 'mpesa';
                        }
                        
                        // Strict check for bypass methods (CBE, E-Pay, E-Birr, etc.)
                        const isEbirrOnly = (methodName.includes('ebirr') || methodName.includes('e-birr')) && !methodName.includes('tele');
                        const isOtherBypass = methodName.includes('cbe') || methodName.includes('bank') || methodName.includes('epay') || methodName.includes('birr');
                        
                        if ((isOtherBypass || isEbirrOnly) && !methodName.includes('tele')) {
                            return 'bypass'; 
                        }
                        
                        // If it explicitly says telebirr, or if we found an active element but it's not the others
                        if (methodName.includes('tele') || activeEl) {
                            return 'telebirr';
                        }
                        
                        // Default fallback - only if no active element found yet
                        return 'telebirr';
                    };
                    
                    const validatePhone = (val, showMessages = true) => {
                        // Remove non-digits
                        let cleanVal = val.replace(/\D/g, '');
                        
                        // Strip common 251 prefix
                        if (cleanVal.startsWith('251')) cleanVal = cleanVal.slice(3);
                        
                        // Internal base number (stripping leading 0)
                        let hasLeadingZero = cleanVal.startsWith('0');
                        let baseVal = hasLeadingZero ? cleanVal.slice(1) : cleanVal;
                        
                        const paymentMethod = getSelectedPaymentMethod();
                        const isMpesa = paymentMethod === 'mpesa';
                        const isTelebirr = paymentMethod === 'telebirr';
                        const isBypass = paymentMethod === 'bypass';
                        
                        // RESTORE MISSING VARIABLES
                        const validPrefix = isMpesa ? '07' : '09';
                        const validShortPrefix = isMpesa ? '7' : '9';
                        const providerName = isMpesa ? 'M-Pesa (Safaricom)' : 'Telebirr';
                        
                        // Immediate check for prefix mismatch while typing
                        if (!isBypass) {
                            const firstDigit = hasLeadingZero ? cleanVal[1] : cleanVal[0];
                            const secondDigit = (hasLeadingZero ? cleanVal[1] : cleanVal[0]) || '';
                            
                            // If user types '0' then its wait for the next digit
                            if (cleanVal === '0') return { valid: false, formatted: cleanVal };

                            // Strict digit check
                            if (secondDigit && secondDigit !== '7' && secondDigit !== '9') {
                                if (showMessages) showValidation('error', `Phone must start with 09 or 07 (You entered 0${secondDigit})`);
                                return { valid: false, formatted: cleanVal };
                            }

                            if (isTelebirr && secondDigit === '7') {
                                if (showMessages) showValidation('error', `This is an M-Pesa number. Please switch to M-Pesa.`);
                                return { valid: false, formatted: cleanVal };
                            }
                            if (isMpesa && secondDigit === '9') {
                                if (showMessages) showValidation('error', `This is a Telebirr number. Please switch to Telebirr.`);
                                return { valid: false, formatted: cleanVal };
                            }
                        }
                        
                        // Length checks - Ethiopian numbers are 9 digits (without 0) or 10 digits (with 0)
                        // Allow flexible input - don't auto-add 0
                        if (baseVal.length < 9) {
                            if (showMessages) showValidation('hint', `Enter 9 or 10 digit number`);
                            return { valid: false, formatted: cleanVal };
                        }
                        
                        if (baseVal.length === 9) {
                            // 9 digits without leading 0 (e.g., 912345678)
                            if (isBypass) {
                                if (showMessages) showValidation('success', `✔ Valid`);
                                return { valid: true, formatted: cleanVal }; // Keep as entered
                            }

                            if (baseVal.startsWith(validShortPrefix)) {
                                if (showMessages) showValidation('success', `✔ Valid`);
                                return { valid: true, formatted: cleanVal }; // Keep as entered, no auto-add 0
                            } else {
                                if (showMessages) showValidation('error', `Invalid prefix for ${providerName}. Use ${validPrefix.slice(1)} or ${validPrefix}.`);
                                return { valid: false, formatted: cleanVal };
                            }
                        }
                        
                        if (baseVal.length > 9) {
                            // Check for 10 digits starting with 0
                            if (cleanVal.length === 10 && cleanVal.startsWith('0')) {
                                if (isBypass) {
                                    if (showMessages) showValidation('success', `✔ Valid`);
                                    return { valid: true, formatted: cleanVal };
                                }

                                if (cleanVal.startsWith(validPrefix)) {
                                    if (showMessages) showValidation('success', `✔ Valid`);
                                    return { valid: true, formatted: cleanVal };
                                }
                            }
                            
                            // Also accept 9 digits without 0 that got here (edge case)
                            if (cleanVal.length === 9 && !cleanVal.startsWith('0')) {
                                if (isBypass || cleanVal.startsWith(validShortPrefix)) {
                                    if (showMessages) showValidation('success', `✔ Valid`);
                                    return { valid: true, formatted: cleanVal };
                                }
                            }
                            
                            // Otherwise it's invalid
                            if (showMessages) {
                                if (cleanVal.length > 10) showValidation('error', `Number too long`);
                                else if (!isBypass) showValidation('error', `Use ${validShortPrefix}XXXXXXXX or ${validPrefix}XXXXXXXX`);
                                else showValidation('error', `Invalid format`);
                            }
                            return { valid: false, formatted: cleanVal.slice(0, 10) };
                        }
                        
                        return { valid: false, formatted: cleanVal };
                    };
                    
                    phoneInput.addEventListener('input', (e) => {
                        const result = validatePhone(e.target.value);
                        if (result.formatted !== e.target.value.replace(/\D/g, '') && result.formatted.length <= 10) {
                            e.target.value = result.formatted;
                        }
                    });
                    
                    // Final validation on blur
                    phoneInput.addEventListener('blur', (e) => {
                        const result = validatePhone(e.target.value);
                        if (result.formatted && result.formatted.length <= 10) {
                            e.target.value = result.formatted;
                        }
                    });
                    
                    // Re-validate when focused (payment method might have changed)
                    phoneInput.addEventListener('focus', () => {
                        if (phoneInput.value.length > 0) {
                            validatePhone(phoneInput.value);
                        }
                    });
                    
                    // Watch for ALL clicks in the form to re-validate immediately
                    chapaInline.addEventListener('click', () => {
                        // Use multiple timeouts to catch Chapa's slow DOM updates during method switches
                        [10, 100, 300, 600].forEach(ms => {
                            setTimeout(() => {
                                if (phoneInput.value.length > 0) {
                                    validatePhone(phoneInput.value);
                                } else {
                                    // Re-prompt for empty field on method change
                                    validatePhone('');
                                }
                            }, ms);
                        });
                    }, true); // Use capture to trigger before other events
                }
            }, 1000); // Wait for Chapa to render its form
            
            const chapa = new ChapaCheckout({
                publicKey: 'CHAPUBK-s9JQu74c7hAcdPPGxaAF6aT22Ih4HNtm',
                amount: Math.round(amount * 100) / 100,
                currency: 'ETB',
                tx_ref: 'paxyo-' + this.userId + '-' + Date.now(), // More traceable
                title: 'Add Funds',
                description: 'Deposit to Paxyo',
                email: 'user-' + this.userId + '@paxyo.com',
                first_name: this.userName || 'Paxyo',
                last_name: 'User',
                showFlag: true,
                showPaymentMethodsNames: true,
                callbackUrl: window.location.origin + '/webhook_handler.php',
            //    returnUrl: window.location.origin + '/net/smm.php?deposit_complete=1',
                onSuccessfulPayment: (result, refId) => {
                    console.log('✅ Payment Success:', result);
                    paymentSubmitted = true;
                    chapaWrapper.classList.add('hidden');
                    this.verifyDeposit(amount, refId || result?.tx_ref || 'ref-' + Date.now());
                },
                onPaymentFailure: (error) => {
                    console.log('Payment callback:', error);
                    const lowerErr = typeof error === 'string' ? error.toLowerCase() : '';
                    
                    // Helper function to show "Try Again" and hide wrapper
                    const showTryAgain = (message) => {
                        chapaWrapper.classList.add('hidden');
                        chapaInline.innerHTML = '';
                        depositBtn.classList.remove('hidden');
                        depositBtn.innerHTML = `
                            <span class="text-lg">🔄 Try Again</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        `;
                        this.showToast(message, 'error');
                        this.showLoading(false);
                    };
                    
                    // COMPLETELY SUPPRESS phone/validation errors - Chapa SDK fires these incorrectly
                    // even when the phone is valid and payment is processing successfully
                    if (lowerErr.includes('phone') || 
                        lowerErr.includes('mobile') || 
                        lowerErr.includes('valid') ||
                        lowerErr.includes('number') ||
                        lowerErr.includes('format') ||
                        lowerErr.includes('required') ||
                        lowerErr.includes('insert') ||
                        lowerErr.includes('enter')) {
                        console.log('🔇 Suppressing Chapa validation error:', error);
                        return; // Silently ignore - these are false positives
                    }
                    
                    // Handle "Failed to fetch" / Network errors - Close wrapper, show Try Again
                    if (lowerErr.includes('fetch') || lowerErr.includes('network') || 
                        lowerErr.includes('server') || lowerErr.includes('failed to fetch') ||
                        lowerErr.includes('connection') || lowerErr.includes('timeout')) {
                        showTryAgain('Connection failed. Please check your internet and try again.');
                        return;
                    }
                    
                    // Suppress regional service errors
                    if (lowerErr.includes('no service found') || 
                        lowerErr.includes('country') || 
                        lowerErr.includes('not supported') ||
                        lowerErr.includes('region')) {
                        showTryAgain('Payment service unavailable in your region.');
                        return;
                    }
                    
                    // Only show real payment failures (cancelled, declined, etc.)
                    if (lowerErr.includes('cancel') || lowerErr.includes('decline') || 
                        lowerErr.includes('failed') || lowerErr.includes('reject')) {
                        showTryAgain(typeof error === 'string' ? error : 'Payment failed. Please try again.');
                        return;
                    }
                    
                    // For any other unknown error, just log it but don't disrupt the UI
                    console.log('⚠️ Unknown Chapa error (suppressed):', error);
                },
                onClose: () => {
                    console.log('Payment closed');
                    this.showLoading(false);
                    setTimeout(() => {
                        depositBtn.classList.remove('hidden');
                        chapaWrapper.classList.add('hidden');
                        chapaWrapper.classList.remove('user-has-submitted'); // Reset on close
                    }, 300);
                }
            });
            
            // CAPTURE CLICK: Listen for the "Pay Now" button click inside the container
            // We use capture: true to ensure we set the flag BEFORE onPaymentFailure might trigger
            chapaInline.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                if (btn) {
                    console.log('💳 User initiated payment click');
                    chapaWrapper.classList.add('user-has-submitted');
                }
            }, true);
            
            try {
                // Chapa inline SDK only needs initialize() to render and start
                // Note: CORS will only work on your registered domain (paxyo.com), not localhost
                chapa.initialize();
                this.showLoading(false);
            } catch(e) {
                console.error('Chapa Error:', e);
                this.showLoading(false);
                depositBtn.classList.remove('hidden');
                chapaWrapper.classList.add('hidden');
                
                // Check if it's a CORS/localhost issue
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    this.showToast('Payment works only on production site (paxyo.com)', 'error');
                } else {
                    this.showToast('Could not initialize payment. Please try again.', 'error');
                }
            }
        },
        
        // Check for deposit completion (called on page load)
        checkDepositReturn() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Check if returning from Chapa
            if (urlParams.get('deposit_complete') === '1') {
                window.history.replaceState({}, document.title, window.location.pathname);
                this.showToast('Checking payment status...', 'info');
                this.switchTab('deposit');
            }
        },
        
        async verifyDeposit(amount, refId) {
             // Keep loading state
             try {
                 const res = await fetch('deposit_handler.php', {
                     method: 'POST',
                     headers: {'Content-Type': 'application/json'},
                     body: JSON.stringify({ amount: amount, reference_id: refId })
                 });
                 const data = await res.json();
                 
                 if (data.status === 'success') {
                     this.showToast('Deposit successful! Balance updated.', 'success');
                     
                     // Update balance locally immediately
                     const newBalance = parseFloat(data.new_balance);
                     this.updateBalance(newBalance);
                     
                     // Reset form
                     document.getElementById('deposit-amount').value = '';
                     document.getElementById('deposit-balance-display').textContent = newBalance.toFixed(2) + ' ETB';
                     
                     // Keep deposit button visible for another deposit
                     const depositBtn = document.getElementById('deposit-btn');
                     const chapaWrapper = document.getElementById('chapa-container-wrapper');
                     depositBtn.classList.remove('hidden');
                     chapaWrapper.classList.add('hidden');
                     
                     // Reload deposit history
                     await this.loadDepositHistory();
                     
                     // Show one-time guide to deposit history (scroll and highlight)
                     this.showDepositHistoryGuide();
                     
                 } else {
                     throw new Error(data.message);
                 }
             } catch (e) {
                 this.showToast(e.message || 'Error verifying deposit', 'error');
              } finally {
                  this.showLoading(false);
              }
         },
         
         // Reset Deposit UI
         resetDepositUI() {
             const depositBtn = document.getElementById('deposit-btn');
             const chapaWrapper = document.getElementById('chapa-container-wrapper');
             const chapaInline = document.getElementById('chapa-inline-form');
             const errorContainer = document.getElementById('chapa-error-container');
             
             if (chapaWrapper) chapaWrapper.classList.add('hidden');
             if (chapaWrapper) chapaWrapper.classList.remove('user-has-submitted');
             if (chapaInline) chapaInline.innerHTML = '';
             if (errorContainer) {
                 errorContainer.classList.add('hidden');
                 errorContainer.innerHTML = '';
             }
             
             if (depositBtn) {
                 depositBtn.classList.remove('hidden');
                 depositBtn.innerHTML = `
                     <span class="text-lg">Deposit</span>
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2-2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                     </svg>
                 `;
             }
         },
         
         showDepositHistoryGuide() {
            // Check if user has seen this guide before
            if (localStorage.getItem('deposit_history_guide_shown')) {
                return;
            }
            
            // Scroll to deposit history section
            const depositHistorySection = document.getElementById('recent-deposits-list');
            if (depositHistorySection) {
                setTimeout(() => {
                    depositHistorySection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Add glowing border animation
                    depositHistorySection.classList.add('guide-highlight');
                    
                    // Show toast
                    this.showToast('Check your deposit history below! 👇', 'success');
                    
                    // Remove highlight after 4 seconds
                    setTimeout(() => {
                        depositHistorySection.classList.remove('guide-highlight');
                    }, 4000);
                    
                    // Mark as shown
                    localStorage.setItem('deposit_history_guide_shown', 'true');
                }, 800);
            }
        },

        // --- History Logic ---

        switchTab(tab) {
            // Instant tab switching for super-reactivity
            const tabViews = ['order', 'history', 'deposit', 'refer'];
            
            // Update Navigation Buttons instantly
            document.querySelectorAll('.nav-item').forEach(btn => {
                const isActive = btn.dataset.tab === tab;
                btn.classList.toggle('active', isActive);
                if (isActive && this.isTelegramApp && window.Telegram?.WebApp?.HapticFeedback) {
                    window.Telegram.WebApp.HapticFeedback.selectionChanged();
                }
            });
            
            // Switch Views instantly
            tabViews.forEach(v => {
                const view = document.getElementById(v + '-view');
                if (view) view.style.display = (v === tab) ? 'block' : 'none';
            });
            
            // Tab-specific fast actions
            if (tab === 'history') {
                this.fetchOrders();
            } else if (tab === 'deposit') {
                const balanceEl = document.getElementById('deposit-balance-display');
                if (balanceEl) balanceEl.textContent = this.formatETB(this.userBalance);
                this.loadDepositHistory();
            } else if (tab === 'refer') {
                this.initChat();
            }
            
            const sw = document.getElementById('tg-scroll-wrapper');
            if (sw) sw.scrollTop = 1; // Use 1 instead of 0 to prevent Telegram collapse
        },

        // --- Chat Logic ---
        chatInitialized: false,
        chatPollInterval: null,
        chatEventSource: null,
        
        initChat() {
            if (this.chatInitialized) {
                this.scrollToBottomChat();
                return;
            }
            
            const chatForm = document.getElementById('chat-form');
            const chatInput = document.getElementById('chat-input');
            const chatContainer = document.getElementById('chat-messages');

            // Initialize Theme Toggle Logic Here too since it's in the same tab
            const themeSwitch = document.getElementById('theme-switch');
            if (themeSwitch) {
                // Set initial state
                if (document.body.classList.contains('light-mode')) {
                    themeSwitch.checked = true;
                }
                
                themeSwitch.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        document.body.classList.add('light-mode');
                        localStorage.setItem('theme', 'light');
                    } else {
                        document.body.classList.remove('light-mode');
                        localStorage.setItem('theme', 'dark');
                    }
                });
            }

            if (chatForm) {
                chatForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const msg = chatInput.value.trim();
                    if (msg) {
                        this.sendChatMessage(msg);
                        chatInput.value = '';
                    }
                });
            }

            // Real-time Chat Stream (SSE)
            this.initChatStream();
            
            this.chatInitialized = true;
        },

        initChatStream() {
            if (this.chatEventSource) this.chatEventSource.close();
            
            // Append UID for cache busting and identification
            this.chatEventSource = new EventSource('chat_stream.php');
            
            this.chatEventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    if (data.type === 'messages') {
                        this.renderChatMessages(data.messages);
                    }
                } catch (e) {
                    console.error('Chat stream parse error:', e);
                }
            };
            
            this.chatEventSource.onerror = (err) => {
                console.warn('Chat stream error (reconnecting):', err);
            };
        },

        async sendChatMessage(message) {
            try {
                // Optimistic UI update
                this.appendMessage({
                    sender: 'user',
                    message: message,
                    created_at: new Date().toISOString()
                });

                const res = await fetch('chat_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'send', message: message })
                });

                if (!res.ok) {
                    throw new Error(`Server returned ${res.status}`);
                }
                
                const data = await res.json();
                
                if (!data.success) {
                    this.showToast(data.error || 'Failed to send', 'error');
                }
            } catch (e) {
                console.error('Chat error:', e);
                this.showToast('Connection error', 'error');
            }
        },

        renderChatMessages(messages) {
            const container = document.getElementById('chat-messages');
            if (!container) return;
            
            // Should verify distinct messages to avoid redrawing if nothing changed? 
            // For simplicity, we redraw inner content but keep scroll position if at bottom
            
            const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
            
            if (messages.length === 0) {
                 container.innerHTML = `
                     <div class="text-center text-gray-500 text-xs py-10 opacity-70">
                         <p>Welcome to Paxyo Support!</p>
                         <p class="mt-1">Messages are secure and private.</p>
                     </div>`;
                 return;
            }

            container.innerHTML = messages.map(msg => `
                <div class="chat-bubble ${msg.sender === 'user' ? 'user self-end bg-smm-accent text-white' : 'admin self-start bg-gray-700 text-gray-200'} max-w-[85%] rounded-2xl px-4 py-2 mb-2 relative">
                    <div class="text-sm">${this.escapeHtml(msg.message)}</div>
                    <div class="text-[9px] opacity-70 text-right mt-1 pt-0.5 border-t border-white/10">
                        ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </div>
                </div>
            `).join('');
            
            if (isAtBottom) {
                this.scrollToBottomChat();
            }
        },
        
        appendMessage(msg) {
             const container = document.getElementById('chat-messages');
             // Remove empty state if present
             if (container.querySelector('.text-center')) {
                 container.innerHTML = '';
             }
             
             const div = document.createElement('div');
             div.innerHTML = `
                <div class="chat-bubble user self-end bg-smm-accent text-white max-w-[85%] rounded-2xl px-4 py-2 mb-2 relative float-right clear-both">
                    <div class="text-sm">${this.escapeHtml(msg.message)}</div>
                    <div class="text-[9px] opacity-70 text-right mt-1 pt-0.5 border-t border-white/10">
                        ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </div>
                </div>
             `;
             container.appendChild(div.firstElementChild);
             this.scrollToBottomChat();
        },

        scrollToBottomChat() {
            const container = document.getElementById('chat-messages');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },
        
        escapeHtml(unsafe) {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        },

        async loadDepositHistory() {
             const list = document.getElementById('recent-deposits-list');
             if (!list) return;
             
             try {
                 const res = await fetch('get_deposits.php');
                 const data = await res.json();
                 
                 if (data.length === 0) {
                     list.innerHTML = '<div class="text-center text-gray-600 text-sm py-4">No recent deposits</div>';
                     return;
                 }
                 
                 list.innerHTML = data.map(d => `
                    <div class="bg-smm-card/20 border border-gray-800/50 rounded-lg p-3 flex justify-between items-center transition-all hover:bg-white/5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-green-500/10 flex items-center justify-center text-green-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            </div>
                            <div>
                                <div class="text-white text-sm font-bold">+${parseFloat(d.amount).toFixed(2)} ETB</div>
                                <div class="text-[10px] text-gray-400 font-mono tracking-wide">#${d.reference_id || 'Ref-ID'}</div>
                                <div class="text-[10px] text-gray-500">${new Date(d.created_at).toLocaleDateString(undefined, {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'})}</div>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-1 rounded bg-green-500/10 text-green-500 border border-green-500/20 capitalize font-medium">${d.status}</span>
                    </div>
                 `).join('');
                 
             } catch (e) {
                 console.error('Error loading deposits:', e);
                 list.innerHTML = '<div class="text-center text-gray-600 text-sm py-4">Failed to load history</div>';
             }
        },
        
        initHistory() {
            // Refresh button
            const refreshBtn = document.getElementById('refresh-history');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.fetchOrders(true); // force refresh
                });
            }
            
            // Search toggle button
            const searchToggle = document.getElementById('toggle-history-search');
            const searchContainer = document.getElementById('history-search-container');
            const searchInput = document.getElementById('history-search-input');
            
            if (searchToggle && searchContainer && searchInput) {
                searchToggle.addEventListener('click', () => {
                    const isVisible = searchContainer.style.display !== 'none';
                    searchContainer.style.display = isVisible ? 'none' : 'block';
                    if (!isVisible) {
                        searchInput.focus();
                    } else {
                        searchInput.value = '';
                        this.renderHistory();
                    }
                });
                
                // Search input - real-time filtering
                searchInput.addEventListener('input', () => {
                    this.renderHistory();
                });
            }
            
            // Filter chips
            document.querySelectorAll('.filter-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                   document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                   chip.classList.add('active');
                   this.renderHistory(chip.dataset.filter);
                });
            });
        },
        
        async fetchOrders(force = false, skipCheck = false) {
             const list = document.getElementById('order-history-list');
             if (force) list.innerHTML = '<div class="text-center text-gray-500 py-10">Refreshing...</div>';
             
             // Check status in background
             if (!skipCheck) {
                 fetch('check_order_status.php')
                     .then(r=>r.json())
                     .then(data => {
                         if (data.updated > 0) {
                             console.log('Status changes detected, refreshing list...');
                             this.showToast(`${data.updated} orders updated`, 'info');
                             this.fetchOrders(false, true); // Refresh data, skip check
                         }
                     })
                     .catch(console.error);
             }
             
             try {
                 const res = await fetch('get_orders.php');
                 const data = await res.json();
                 
                 this.orders = data.orders || [];
                 this.renderHistory();
                 
             } catch(e) {
                 console.error('Fetch orders error:', e);
                 list.innerHTML = '<div class="text-center text-red-500 py-10">Failed to load history</div>';
             }
        },
        
        renderHistory(filter = 'all') {
             const list = document.getElementById('order-history-list');
             // Current active filter
             if (filter === 'all') {
                 // Try to find active chip to be sure
                 const active = document.querySelector('.filter-chip.active');
                 if (active) filter = active.dataset.filter;
             }
             
             // Define normalization at the top to avoid reference errors
             const normalizeStatus = (status) => {
                 if (!status || status.trim() === '') return 'processing';
                 const s = status.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z_]/g, '');
                 // Map common variations
                 const statusMap = {
                     'in_progress': 'processing',
                     'inprogress': 'processing',
                     'complete': 'completed',
                     'success': 'completed',
                     'canceled': 'cancelled',
                     'refunded': 'cancelled'
                 };
                 return statusMap[s] || s;
             };

             let orders = this.orders || [];
             
             // Apply status filter
             if (filter !== 'all') {
                 orders = orders.filter(o => normalizeStatus(o.status) === filter);
             }
             
             // Apply search filter
             const searchInput = document.getElementById('history-search-input');
             if (searchInput && searchInput.value.trim()) {
                 const searchTerm = searchInput.value.trim().toLowerCase();
                 orders = orders.filter(o => {
                     const serviceId = String(o.service_id || '').toLowerCase();
                     const orderId = String(o.id || '').toLowerCase();
                     const apiOrderId = String(o.api_order_id || '').toLowerCase();
                     const serviceName = String(o.service_name || '').toLowerCase();
                     const link = String(o.link || '').toLowerCase();
                     
                     return serviceId.includes(searchTerm) || 
                            orderId.includes(searchTerm) || 
                            apiOrderId.includes(searchTerm) ||
                            serviceName.includes(searchTerm) ||
                            link.includes(searchTerm);
                 });
             }
             
             if (orders.length === 0) {
                 list.innerHTML = '<div class="text-center text-gray-500 py-10">No orders found</div>';
                 return;
             }
             
             // Table Render

             

             const rows = orders.map(o => {
                 const normalizedStatus = normalizeStatus(o.status);
                 const statusClass = `status-${normalizedStatus}`;
                 const displayStatus = (o.status && o.status.trim() !== '') ? o.status : 'In Progress';
                 const date = new Date(o.created_at).toLocaleString();
                 const serviceId = o.service_id || 'N/A';
                 const orderId = o.id || 'N/A';
                 const apiOrderId = o.api_order_id;
                 const serviceName = o.service_name || `Order #${orderId}`;
                 const displayRemains = (o.remains !== null && o.remains !== undefined && o.remains !== '') ? parseInt(o.remains).toLocaleString() : '-';
                 const displayStart = (o.start_count !== null && o.start_count !== undefined && o.start_count !== '') ? parseInt(o.start_count).toLocaleString() : '-';
                 
                 return `
                    <tr class="border-b border-gray-800 last:border-0 hover:bg-white/5 transition-colors" data-order-id="${orderId}">
                        <td class="p-3">
                            <div class="flex flex-col min-w-[80px]">
                             
                                ${apiOrderId && apiOrderId != 0 ? `<span class="font-mono text-[9px] text-smm-accent mt-0.5">ID: ${apiOrderId}</span>` : ''}
                                <span class="order-status ${statusClass} text-[9px] px-1.5 py-0.5 rounded-sm inline-block mt-1.5 uppercase font-bold w-fit">${displayStatus}</span>
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-col">
                                <span class="font-medium text-sm text-gray-200 truncate max-w-[150px]">${serviceId} ${serviceName}</span>
                                <a href="${o.link || '#'}" target="_blank" class="text-xs text-blue-400 hover:text-blue-300 truncate max-w-[150px] block">${o.link || 'N/A'}</a>
                            </div>
                        </td>
                        <td class="p-3 text-center">
                            <span class="text-sm text-white">${o.quantity || 0}</span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="text-sm font-mono text-gray-400">${displayStart}</span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="text-sm font-mono text-gray-400">${displayRemains}</span>
                        </td>
                        <td class="p-3 text-right">
                            <span class="text-sm font-mono text-smm-success">${this.formatETB(o.charge)}</span>
                        </td>
                        <td class="p-3 text-right">
                            <span class="text-xs text-gray-500">${date.split(',')[0]}</span>
                        </td>
                        <td class="p-3 text-right">
                             ${this.renderActionButtons(o)}
                        </td>
                    </tr>
                 `;
             }).join('');
             
             list.innerHTML = `
                <div class="overflow-x-auto rounded-lg border border-gray-800 bg-smm-card">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-800 bg-black/20 text-xs text-gray-500 uppercase">
                                <th class="p-3 font-medium">Order ID</th>
                                <th class="p-3 font-medium">Service / Link</th>
                                <th class="p-3 font-medium text-center">Qty</th>
                                <th class="p-3 font-medium text-center">Start</th>
                                <th class="p-3 font-medium text-center">Remains</th>
                                <th class="p-3 font-medium text-right">Charge</th>
                                <th class="p-3 font-medium text-right">Date</th>
                                <th class="p-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
             `;
        },
        
        bindSearchExpandableEvents() {
            const setupSearch = (inputId, toggleId, titleId, clearId, emptyId, listId, itemSelector) => {
                const input = document.getElementById(inputId);
                const toggle = document.getElementById(toggleId);
                const title = document.getElementById(titleId);
                const clear = document.getElementById(clearId);
                const empty = document.getElementById(emptyId);
                const list = document.getElementById(listId);
                
                if (!input || !toggle || !list) return;
                
                const box = input.closest('.modal-search');

                const focusInput = (e) => {
                    if (e) e.stopPropagation();
                    input.focus();
                };

                const clearSearch = (e) => {
                    if (e) e.stopPropagation();
                    input.value = '';
                    input.dispatchEvent(new Event('input'));
                    input.focus();
                };

                if (title) {
                    title.style.cursor = 'pointer';
                    title.addEventListener('click', focusInput);
                }
                if (box) {
                    box.style.cursor = 'text';
                    box.addEventListener('click', focusInput);
                }
                if (clear) {
                    clear.addEventListener('click', clearSearch);
                }
                toggle.addEventListener('click', focusInput);
                
                input.addEventListener('input', () => {
                   const term = input.value.toLowerCase().trim();
                   let visibleCount = 0;
                   
                   const items = list.querySelectorAll(itemSelector);
                   items.forEach(el => {
                       const originalText = el.dataset.originalText || el.innerHTML;
                       if (!el.dataset.originalText) el.dataset.originalText = originalText;

                       const text = el.textContent.toLowerCase();
                       const isMatch = text.includes(term);
                       el.style.display = isMatch ? '' : 'none';
                       
                       if (isMatch) {
                           visibleCount++;
                           if (term.length > 0) {
                               const nameEl = el.querySelector('.dropdown-item-name') || el;
                               const catEl = el.querySelector('.text-[9px].text-gray-500');
                               const idEl = el.querySelector('.text-[10px].font-mono');

                               if (nameEl) nameEl.innerHTML = SMM.highlightMatch(nameEl.textContent, term);
                               if (catEl) catEl.innerHTML = SMM.highlightMatch(catEl.textContent, term);
                               if (idEl) idEl.innerHTML = SMM.highlightMatch(idEl.textContent, term);
                           } else {
                               // Reset to original if empty
                               el.innerHTML = el.dataset.originalText;
                           }
                       }
                   });

                   if (clear) {
                       clear.classList.toggle('hidden', term === '');
                   }

                   if (empty) {
                       empty.style.display = (visibleCount === 0) ? 'block' : 'none';
                   }
                });
                
                input.addEventListener('click', (e) => e.stopPropagation());
            };
            
            setupSearch('category-search-input', 'category-search-toggle', 'category-modal-title', 'category-search-clear', 'category-empty-state', 'category-list', '.dropdown-item');
            setupSearch('service-search-input', 'service-search-toggle', 'service-modal-title', 'service-search-clear', 'service-empty-state', 'service-list', '.dropdown-item');
        },
        
        async fetchAlerts() {
            try {
                const res = await fetch('get_alerts.php');
                const data = await res.json();
                
                if (data.alerts) {
                    this.alerts = data.alerts;
                    if (data.unread_count > 0) {
                        this.elements.alertDot.classList.remove('hidden');
                    } else {
                        this.elements.alertDot.classList.add('hidden');
                    }
                } else {
                    this.alerts = [];
                }
            } catch (e) {
                console.error('Error fetching alerts:', e);
                this.alerts = [];
            }
        },

        showAlertModal() {
            this.renderAlerts();
            this.elements.alertModal.classList.add('visible');
            
            // Mark alerts as read
            if (this.alerts.some(a => a.is_read == 0)) {
                fetch('mark_alerts_read.php')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.elements.alertDot.classList.add('hidden');
                            this.alerts.forEach(a => a.is_read = 1);
                            // Optionally re-render to remove small blue dots
                            this.renderAlerts();
                        }
                    })
                    .catch(console.error);
            }
        },
        
        // Load services from API
        async loadServices() {
            // Only show loading if we don't have services yet
            const needsLoading = this.services.length === 0;
            if (needsLoading) this.showLoading(true);
            try {
                // Prioritize speed: fetch without timestamp to hit browser/server cache
                const [servicesRes, recommendedRes] = await Promise.all([
                    fetch('get_service.php'), // Will use server-side file cache if available
                    fetch('get_recommended.php')
                ]);
                
                const data = await servicesRes.json();
                this.recommendedServices = await recommendedRes.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                this.services = data
                    .filter(item => !this.hiddenServices.has(parseInt(item.service)))
                    .map(item => ({
                        id: item.service,
                        category: item.category,
                        name: item.name,
                        type: item.type,
                        rate: parseFloat(item.rate),
                        min: parseInt(item.min),
                        max: parseInt(item.max),
                        averageTime: item.average_time,
                        refill: item.refill,
                        cancel: item.cancel
                    }));
                
                // Extract unique categories
                this.categories = [...new Set(this.services.map(s => s.category))];
                
                console.log('Services loaded:', this.services.length);
                
                // If history is visible, re-render now that we have service data (for cancel/refill check)
                if (document.getElementById('history-view')?.style.display !== 'none') {
                    this.renderHistory();
                }

                // Background Cache Refresh
                // Updates the server cache for the NEXT visit.
                setTimeout(() => {
                    fetch('get_service.php?refresh=1')
                        .then(() => console.log('Background cache updated'))
                        .catch(e => console.warn('Cache update failed:', e));
                }, 100);

            } catch (error) {
                console.error('Error loading services:', error);
                this.showToast('Failed to load services. Please refresh.', 'error');
            } finally {
                if (needsLoading) this.showLoading(false);
            }
        },
        
        // Select social platform
        // Select social platform
        // Select social platform
        selectPlatform(platform, fromSearch = false) {
            this.selectedPlatform = platform;
            if (!fromSearch) {
                this.selectedService = null;
            }
            
            // Update UI
            document.querySelectorAll('.social-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.platform === platform);
            });
            
            // Update link placeholder based on platform
            const placeholders = {
                instagram: 'https://instagram.com/p/...',
                tiktok: 'https://tiktok.com/@username/video/...',
                facebook: 'https://facebook.com/page',
                youtube: 'https://youtube.com/watch?v=...',
                twitter: 'https://x.com/username/status/...',
                telegram: 'https://t.me/channel/123 or https://t.me/channel',
                whatsapp: 'https://wa.me/number',
                top: 'Enter link',
                other: 'https://example.com'
            };

            const labels = {
                instagram: 'Link',
                tiktok: 'Link',
                facebook: 'Link',
                youtube: 'Link',
                twitter: 'Link',
                telegram: 'Link/Username',
                whatsapp: 'WhatsApp Number',
                top: 'Link',
                other: 'Link'
            };

            if (this.elements.orderLink) {
                this.elements.orderLink.placeholder = placeholders[platform] || 'Enter link';
            }
            
            // Update Label Text (for="order-link")
            const labelEl = document.querySelector('label[for="order-link"]');
            if (labelEl) {
                labelEl.textContent = labels[platform] || 'Link';
            }
            
            if (fromSearch) return;

            // Auto-select category for Top and special cases
            if (platform === 'top') {
                this.selectCategory('Top Services');
                // Instant choice (Open Modal)
                this.showServiceModal();
            } else {
                this.selectedCategory = null;
                this.elements.categoryText.textContent = 'Select a category';
                this.elements.categoryText.className = 'placeholder';
            }
            
            // Reset other dropdowns
            this.elements.serviceText.textContent = 'Select a service';
            this.elements.serviceText.className = 'placeholder';
            this.elements.serviceInfo.classList.remove('visible');
            
            // Reset form
            this.elements.orderLink.value = '';
            this.elements.orderQuantity.value = '';
            this.elements.chargeAmount.textContent = '0.00 ETB';

            // Instant feedback
            document.querySelectorAll('.arrow-highlight, .input-highlight').forEach(el => el.classList.remove('arrow-highlight', 'input-highlight'));
            
            if (!this.hasVisited) {
                const categoryCard = document.querySelector('#category-dropdown').parentElement;
                window.scrollTo({ top: categoryCard.offsetTop - 100, behavior: 'auto' }); 
            }
            document.querySelector('#category-dropdown .arrow').classList.add('arrow-highlight');

            // --- ULTRA-BOOST: PRE-RENDER CATEGORY LIST ---
            this.preRenderCategoryList();
        },
        
        preRenderCategoryList() {
            const categories = this.getPlatformCategories();
            if (categories.length === 0) return;

            this.categoryListCache = categories.map(cat => {
                let serviceCount = 0;
                if (this.selectedPlatform === 'top' && cat === 'Top Services') {
                    serviceCount = this.services.filter(s => this.recommendedServices.includes(parseInt(s.id))).length;
                } else {
                    serviceCount = this.services.filter(s => s.category === cat).length;
                }
                
                return `
                    <div class="dropdown-item ${this.selectedCategory === cat ? 'selected' : ''}" data-category="${cat}">
                        <div class="flex justify-between items-center gap-2">
                            <div class="dropdown-item-name flex-1">${cat}</div>
                            <span class="service-count-badge">${serviceCount}</span>
                        </div>
                    </div>
                `;
            }).join('');
            this.lastPlatform = this.selectedPlatform;
            // Inject into DOM immediately while hidden
            this.elements.categoryList.innerHTML = this.categoryListCache;
            this.elements.categoryList.querySelectorAll('.dropdown-item').forEach((item) => {
                item.onclick = (e) => {
                    this.selectCategory(item.dataset.category);
                    this.elements.categoryModal.classList.remove('visible');
                };
            });
        },
        
        // Get categories for selected platform
        getPlatformCategories() {
            // Special case for 'top' (Recommended)
            if (this.selectedPlatform === 'top') {
                return ['Top Services']; // Virtual category
            }

            // Filter categories that contain the platform name (case-insensitive)
            const platformKeywords = {
                instagram: ['Instagram'],
                tiktok: ['TikTok'],
                facebook: ['Facebook'],
                youtube: ['YouTube'],
                twitter: ['Twitter', ' X '],
                telegram: ['Telegram'],
                whatsapp: ['WhatsApp']
            };
            
            // Special case for 'other' - Show everything NOT in the major platforms lists
            if (this.selectedPlatform === 'other') {
                const allKeywords = Object.values(platformKeywords).flat().map(kw => kw.toLowerCase());
                // Add ' x ' as a keyword for Twitter/X to ensure it's excluded from others
                allKeywords.push(' x '); 
                
                const filtered = this.categories.filter(cat => {
                    const catLower = cat.toLowerCase();
                    return !allKeywords.some(kw => catLower.includes(kw));
                });
                return filtered;
            }
            
            const keywords = platformKeywords[this.selectedPlatform] || [this.selectedPlatform];
            
            const filtered = this.categories.filter(cat => {
                const catLower = cat.toLowerCase();
                return keywords.some(kw => catLower.includes(kw.trim().toLowerCase()));
            });
            
            console.log(`Platform: ${this.selectedPlatform}, Found categories:`, filtered.length);
            
            // Do NOT fallback to all categories. If a user selects a platform,
            // they expect to see only that platform's services.
            return filtered;
        },

        // Auto-select platform based on category name
        selectPlatformByService(category) {
            // Ensure we are on the order tab
            this.switchTab('order');

            let platform = 'other';
            const catLower = category.toLowerCase();
            
            if (category === 'Top Services' || this.recommendedServices.some(id => {
                // Check if any service in this category is recommended? No, "Top Services" is a virtual category.
                // But if the category itself implies TOP, we use top.
                return false;
            })) {
                // If the selected category EXPLICITLY came from the "Top Services" view logic, it would be passed as such.
                // But here we are passing the raw category string from the service object.
                // So if the user searched for a service that happens to be in "Instagram Followers", 
                // we should probably select 'instagram' even if it's also a top service.
                // However, if the category argument IS "Top Services", use top.
                if (category === 'Top Services') platform = 'top';
            }
            
            if (platform === 'other') {
                if (catLower.includes('instagram') || catLower.includes(' ig')) {
                    platform = 'instagram';
                } else if (catLower.includes('tiktok')) {
                    platform = 'tiktok';
                } else if (catLower.includes('youtube') || catLower.includes(' yt')) {
                    platform = 'youtube';
                } else if (catLower.includes('facebook') || catLower.includes(' fb')) {
                    platform = 'facebook';
                } else if (catLower.includes('twitter') || catLower.includes(' x ') || catLower.includes(' x-')) {
                    platform = 'twitter';
                } else if (catLower.includes('telegram') || catLower.includes(' tg')) {
                    platform = 'telegram';
                } else if (catLower.includes('whatsapp') || catLower.includes(' wa')) {
                     platform = 'whatsapp';
                }
            }
            
            console.log(`Auto-selecting platform ${platform} for category ${category}`);
            this.selectPlatform(platform, true);
        },
        
        // State for caching rendered lists
        lastPlatform: null,
        lastCategory: null,
        categoryListCache: null,
        serviceListCache: null,

        // Show category modal
        showCategoryModal() {
            // Remove highlight on open
            document.querySelector('#category-dropdown .arrow')?.classList.remove('arrow-highlight');

            // If not pre-rendered (unlikely), render now
            if (!this.categoryListCache || this.lastPlatform !== this.selectedPlatform) {
                this.preRenderCategoryList();
            }
            
            this.elements.categoryList.scrollTo({ top: 0, behavior: 'instant' }); 
            this.elements.categoryModal.classList.add('visible');
            
            const searchInput = document.getElementById('category-search-input');
            if (searchInput) {
                searchInput.value = '';
                searchInput.classList.remove('visible');
                searchInput.focus();
            }
        },
        
        // Select category
        selectCategory(category) {
            this.selectedCategory = category;
            this.selectedService = null;
            
            // Update UI
            this.elements.categoryText.textContent = category;
            this.elements.categoryText.className = 'value';
            this.elements.serviceText.textContent = 'Select a service';
            this.elements.serviceText.className = 'placeholder';
            this.elements.serviceInfo.classList.remove('visible');
            
            // Reset charge
            this.elements.orderQuantity.value = '';
            this.elements.chargeAmount.textContent = '0.00 ETB';
            
            // --- Update Placeholder & Label based on Category ---
            const catLower = category.toLowerCase();
            let placeholder = 'Enter link';
            let label = 'Link';

            if (catLower.includes('instagram')) {
                if (catLower.includes('follower')) {
                    placeholder = 'https://instagram.com/username';
                    label = 'Profile URL';
                } else if (catLower.includes('story') || catLower.includes('stories')) {
                    placeholder = 'https://instagram.com/stories/username';
                    label = 'Story/Profile URL';
                } else if (catLower.includes('mention') || catLower.includes('direct')) {
                    placeholder = 'https://instagram.com/username';
                    label = 'Username/Link';
                } else {
                    placeholder = 'https://instagram.com/p/Code...';
                    label = 'Post/Reel URL';
                }
            } else if (catLower.includes('tiktok')) {
                if (catLower.includes('follower')) {
                    placeholder = 'https://tiktok.com/@username';
                    label = 'Profile URL';
                } else if (catLower.includes('live')) {
                     placeholder = 'https://tiktok.com/@username/live';
                     label = 'Profile/Live URL';
                } else {
                    placeholder = 'https://tiktok.com/@username/video/123...';
                    label = 'Video URL';
                }
            } else if (catLower.includes('youtube')) {
                if (catLower.includes('subscriber')) {
                     placeholder = 'https://youtube.com/channel/UC... or https://youtube.com/@handle';
                     label = 'Channel URL';
                } else if (catLower.includes('short')) {
                     placeholder = 'https://youtube.com/shorts/VideoID';
                     label = 'Shorts URL';
                } else {
                     placeholder = 'https://youtube.com/watch?v=VideoID';
                     label = 'Video URL';
                }
            } else if (catLower.includes('facebook')) {
                if (catLower.includes('page') || catLower.includes('follower')) {
                    placeholder = 'https://facebook.com/YourPage';
                    label = 'Page/Profile URL';
                } else {
                    placeholder = 'https://facebook.com/YourPage/posts/123...';
                    label = 'Post URL';
                }
            } else if (catLower.includes('twitter') || catLower.includes(' x ')) {
                if (catLower.includes('follower')) {
                    placeholder = 'https://x.com/username';
                    label = 'Profile URL';
                } else {
                    placeholder = 'https://x.com/username/status/123...';
                    label = 'Tweet URL';
                }
            } else if (catLower.includes('telegram')) {
                if (catLower.includes('member') || catLower.includes('subscriber')) {
                     placeholder = 'https://t.me/channel_link';
                     label = 'Channel/Group Link';
                } else {
                     placeholder = 'https://t.me/channel/123';
                     label = 'Post Link';
                }
            } else if (catLower.includes('whatsapp')) {
                placeholder = 'https://wa.me/1234567890';
                label = 'WhatsApp Link/Number';
            } else if (catLower.includes('website') || catLower.includes('traffic')) {
                placeholder = 'https://yourwebsite.com';
                label = 'Website URL';
            } else if (catLower.includes('discord')) {
                placeholder = 'https://discord.gg/invite';
                label = 'Invite Link';
            }

            if (this.elements.orderLink) {
                this.elements.orderLink.placeholder = placeholder;
            }
            const labelEl = document.querySelector('label[for="order-link"]');
            if (labelEl) {
                labelEl.textContent = label;
            }



            // Scroll instantly
            document.querySelectorAll('.arrow-highlight').forEach(el => el.classList.remove('arrow-highlight'));
            if (!this.hasVisited) {
                const serviceCard = document.querySelector('#service-dropdown').parentElement;
                window.scrollTo({ top: serviceCard.offsetTop - 100, behavior: 'auto' });
            }
            document.querySelector('#service-dropdown .arrow').classList.add('arrow-highlight');

            // --- ULTRA-BOOST: PRE-RENDER SERVICE LIST ---
            this.preRenderServiceList();
        },

        preRenderServiceList() {
            const services = this.getCategoryServices();
            if (services.length === 0) return;

            this.serviceListCache = services.map(svc => {
                const etbRate = (svc.rate * this.rateMultiplier).toFixed(2);
                return `
                    <div class="dropdown-item ${this.selectedService?.id === svc.id ? 'selected' : ''}" data-service-id="${svc.id}">
                        <div class="flex justify-between items-start gap-2 mb-0.5">
                            <span class="text-[10px] font-mono bg-smm-accent/15 text-smm-accent px-1.5 py-0.5 rounded shrink-0 font-bold">#${svc.id}</span>
                            <span class="text-[9px] text-gray-500 uppercase font-bold tracking-tight text-right truncate max-w-[100px]">${svc.category}</span>
                        </div>
                        <div class="dropdown-item-name mb-1.5">${svc.name}</div>
                        <div class="dropdown-item-info font-medium">
                            <span class="text-smm-success">${etbRate} ETB /1000</span>
                            <span class="mx-1 opacity-20">|</span>
                            <span class="opacity-60">Min: ${svc.min.toLocaleString()}</span>
                            <span class="mx-1 opacity-20">|</span>
                            <span class="opacity-60">Max: ${svc.max.toLocaleString()}</span>
                            ${svc.averageTime ? `<span class="mx-1 opacity-20">|</span><span class="text-blue-400">🕒 ${svc.averageTime}</span>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            this.lastCategory = this.selectedCategory;
            // Inject into DOM immediately while hidden
            this.elements.serviceList.innerHTML = this.serviceListCache;
            this.elements.serviceList.querySelectorAll('.dropdown-item').forEach((item) => {
                item.onclick = () => {
                    const serviceId = parseInt(item.dataset.serviceId);
                    const service = this.services.find(s => s.id === serviceId);
                    this.selectService(service);
                    this.elements.serviceModal.classList.remove('visible');
                };
            });
        },
        
        // G,et services for selected category
        getCategoryServices() {
            if (this.selectedPlatform === 'top' && this.selectedCategory === 'Top Services') {
                return this.services.filter(s => this.recommendedServices.includes(parseInt(s.id)));
            }
            return this.services.filter(s => s.category === this.selectedCategory);
        },
        
        // Show service modal
        showServiceModal() {
            // If not pre-rendered (unlikely), render now
            if (!this.serviceListCache || this.lastCategory !== this.selectedCategory) {
                this.preRenderServiceList();
            }
            
            this.elements.serviceList.scrollTo({ top: 0, behavior: 'instant' }); 
            this.elements.serviceModal.classList.add('visible');
            
            const searchInput = document.getElementById('service-search-input');
            if (searchInput) {
                searchInput.value = '';
                searchInput.classList.remove('visible');
                searchInput.focus();
            }
        },
        
        // Custom Smooth Scroll Function
        smoothScrollTo(element, duration) {
            const targetPosition = element.getBoundingClientRect().top + window.pageYOffset - (window.innerHeight / 2) + (element.offsetHeight / 2);
            const startPosition = window.pageYOffset;
            const distance = targetPosition - startPosition;
            let startTime = null;
        
            function animation(currentTime) {
                if (startTime === null) startTime = currentTime;
                const timeElapsed = currentTime - startTime;
                const run = ease(timeElapsed, startPosition, distance, duration);
                window.scrollTo(0, run);
                if (timeElapsed < duration) requestAnimationFrame(animation);
            }
        
            // Ease-in-out function for smoother effect
            function ease(t, b, c, d) {
                t /= d / 2;
                if (t < 1) return c / 2 * t * t + b;
                t--;
                return -c / 2 * (t * (t - 2) - 1) + b;
            }
        
            requestAnimationFrame(animation);
        },

        // Select service
        selectService(service) {
            this.selectedService = service;
            
            // Update Info
            this.elements.serviceText.innerHTML = `<span class="opacity-40 font-mono text-[11px] mr-1.5 font-bold">#${service.id}</span>${service.name}`;
            this.elements.serviceText.className = 'value flex items-center';
            this.elements.serviceMin.textContent = service.min.toLocaleString();
            this.elements.serviceMax.textContent = service.max.toLocaleString();
            
            if (service.averageTime) {
                this.elements.serviceAverageTime.textContent = service.averageTime;
                this.elements.avgTimeRow.style.display = 'flex';
            } else {
                this.elements.avgTimeRow.style.display = 'none';
            }
            const originalRate = (parseFloat(service.rate) * this.rateMultiplier);
            let displayRate = this.formatValue(originalRate) + ' ETB';
            
            if (this.discountPercent > 0) {
                const discountedRate = (originalRate * (1 - (this.discountPercent / 100)));
                displayRate = `<span class="line-through text-gray-500 mr-1">${this.formatValue(originalRate)}</span> <span class="text-smm-accent">${this.formatValue(discountedRate)} ETB</span>`;
                
                // Show holiday name
                if (this.holidayName) {
                    displayRate += ` <span class="text-[10px] text-green-400">(${this.holidayName})</span>`;
                }
            }
            
            this.elements.serviceRate.innerHTML = displayRate;
            this.elements.serviceInfo.classList.add('visible');
            
            // Handle Input Visibility based on Type
            const type = service.type;
            
            // Reset visibility
            this.elements.quantityGroup.classList.remove('hidden');
            this.elements.commentsGroup.classList.add('hidden');
            this.elements.usernameGroup.classList.add('hidden');
            this.elements.answerNumberGroup.classList.add('hidden');
            
            this.elements.orderQuantity.disabled = false;
            this.elements.orderQuantity.value = '';
            
            if (type === 'Custom Comments' || type === 'Custom Comments Package' || type === 'Mentions with Hashtags') {
                this.elements.quantityGroup.classList.add('hidden');
                this.elements.commentsGroup.classList.remove('hidden');
            } else if (type === 'Package') {
                this.elements.quantityGroup.classList.add('hidden');
                this.elements.orderQuantity.value = service.min; // Fixed quantity usually
            } else if (type === 'Poll') {
                this.elements.answerNumberGroup.classList.remove('hidden');
            }
            
            // Update quantity placeholder
            this.elements.orderQuantity.placeholder = `${service.min} - ${service.max}`;
            
            // Calculate charge
            this.calculateCharge();

             // Scroll to Order Form
             setTimeout(() => {
                // Remove highlight from Service
                document.querySelectorAll('.arrow-highlight').forEach(el => el.classList.remove('arrow-highlight'));

                if (!this.hasVisited) {
                    const orderForm = document.querySelector('.order-form');
                    this.smoothScrollTo(orderForm, 1000);
                    
                    // Mark as visited now that they reached the end of the flow
                    localStorage.setItem('smm_visited', 'true');
                    this.hasVisited = true;
                }
                document.getElementById('order-link').classList.add('input-highlight');
            }, 300);
        },
        
        // Calculate charge
        calculateCharge() {
            if (!this.selectedService) {
                this.elements.chargeAmount.textContent = '0.00 ETB';
                return;
            }
            
            const type = this.selectedService.type;
            let quantity = parseInt(this.elements.orderQuantity.value) || 0;
            
            if (type === 'Custom Comments' || type === 'Custom Comments Package' || type === 'Mentions with Hashtags') {
                 const comments = this.elements.orderComments.value;
                 quantity = comments.split('\n').filter(line => line.trim() !== '').length;
                 this.elements.orderQuantity.value = quantity; 
            } else if (type === 'Package') {
                 quantity = parseInt(this.selectedService.min); // Fixed
            }

            let rate = this.selectedService.rate;
            if (this.discountPercent > 0) {
                 rate = rate * (1 - (this.discountPercent / 100));
            }

            const charge = (quantity / 1000) * rate * this.rateMultiplier;
            
            // Show discount badge if active
            const chargeText = this.formatETB(charge);
            if (this.discountPercent > 0) {
                const originalCharge = (quantity / 1000) * this.selectedService.rate * this.rateMultiplier;
                this.elements.chargeAmount.innerHTML = `
                    <span class="line-through text-gray-500 text-xs mr-2">${this.formatValue(originalCharge)} ETB</span>
                    <span class="text-smm-accent">${chargeText}</span>
                    <span class="text-[10px] bg-red-500 text-white px-1 rounded ml-1">-${this.discountPercent}% OFF</span>
                `;
            } else {
                this.elements.chargeAmount.textContent = chargeText;
            }
        },
        
        // Place order
        async placeOrder() {
            if (!this.userCanOrder) {
                this.showToast('System under maintenance. Ordering is temporarily disabled.', 'error');
                return;
            }

            // Validation
            if (!this.selectedPlatform) {
                this.showToast('Please select a social media platform', 'error');
                return;
            }
            
            if (!this.selectedCategory) {
                this.showToast('Please select a category', 'error');
                return;
            }
            
            if (!this.selectedService) {
                this.showToast('Please select a service', 'error');
                return;
            }
            
            const link = this.elements.orderLink.value.trim();
            if (!link) {
                this.showToast('Please enter a link', 'error');
                return;
            }
            
            const quantity = parseInt(this.elements.orderQuantity.value);
            if (!quantity || isNaN(quantity)) {
                this.showToast('Please enter a quantity', 'error');
                return;
            }
            
            if (quantity % 10 !== 0) {
                this.showToast('Quantity must be a multiple of 10', 'error');
                return;
            }
            
            if (quantity < this.selectedService.min) {
                this.showToast(`Minimum quantity is ${this.selectedService.min.toLocaleString()}`, 'error');
                return;
            }
            
            if (quantity > this.selectedService.max) {
                this.showToast(`Maximum quantity is ${this.selectedService.max.toLocaleString()}`, 'error');
                return;
            }
            
            const comments = this.elements.orderComments.value;

            // Calculate charge with discount applied
            let rate = this.selectedService.rate;
            if (this.discountPercent > 0) {
                rate = rate * (1 - (this.discountPercent / 100));
            }
            const charge = (quantity / 1000) * rate * this.rateMultiplier;
            
            if (charge > this.userBalance) {
                this.showToast(`Insufficient balance. You need ${charge.toFixed(2)} ETB but have ${this.userBalance.toFixed(2)} ETB`, 'error');
                return;
            }
            
            // Submit order
            this.showLoading(true);
            try {
                const payload = {
                    service: this.selectedService.id,
                    link: link,
                    quantity: quantity
                };
                
                const type = this.selectedService.type;
                
                if (type === 'Custom Comments' || type === 'Custom Comments Package' || type === 'Mentions with Hashtags') {
                    payload.comments = this.elements.orderComments.value;
                }
                
                if (type === 'Poll') {
                    payload.answer_number = this.elements.orderAnswerNumber.value;
                }
                
                if (!this.elements.usernameGroup.classList.contains('hidden')) {
                     payload.username = this.elements.orderUsername.value;
                }

                const response = await fetch('process_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                
                if (result.error) {
                    throw new Error(result.error);
                }
                
                // Success - Show celebration! 🎉
                this.showToast('Order placed successfully! 🎉', 'success');
                this.showSuccessCelebration();
                
                // Update balance using server response for accuracy
                if (result.new_balance !== undefined) {
                    this.userBalance = parseFloat(result.new_balance);
                } else {
                    this.userBalance -= charge;
                }
                const balanceEl = document.getElementById('user-balance');
                balanceEl.textContent = this.formatETB(this.userBalance);
                balanceEl.classList.add('pulse');
                setTimeout(() => balanceEl.classList.remove('pulse'), 600);
                
                // Reset form
                this.elements.orderLink.value = '';
                this.elements.orderQuantity.value = '';
                this.elements.chargeAmount.textContent = '0.00 ETB';
                
                // Show one-time guide to check order history
                this.showOrderHistoryGuide();
                
            } catch (error) {
                console.error('Order error:', error);
                this.showToast(error.message || 'Failed to place order. Please try again.', 'error');
            } finally {
                this.showLoading(false);
            }
        },
        
        showOrderHistoryGuide() {
            // Check if user has seen this guide before
            if (localStorage.getItem('order_history_guide_shown')) {
                return;
            }
            
            // Find the History tab button
            const historyTab = document.querySelector('.nav-item[data-tab="history"]');
            if (historyTab) {
                setTimeout(() => {
                    // Add glowing border animation
                    historyTab.classList.add('guide-highlight');
                    
                    // Show toast
                    this.showToast('Check your order status in the History tab! 📊', 'success');
                    
                    // Remove highlight after 5 seconds
                    setTimeout(() => {
                        historyTab.classList.remove('guide-highlight');
                    }, 5000);
                    
                    // Mark as shown
                    localStorage.setItem('order_history_guide_shown', 'true');
                }, 1500);
            }
        },
        
        // Open search modal
        openSearchModal() {
            this.elements.searchModal.classList.add('visible');
            this.elements.searchInput.focus();
            
            // Only perform search if container is empty (first open) or query changed
            if (this.elements.searchResults.children.length === 0) {
                this.searchServices(''); 
            }
        },
        
        // Close search modal
        closeSearchModal() {
            this.elements.searchModal.classList.remove('visible');
            this.elements.searchInput.value = '';
        },

        // Optimized Highlight matching text
        highlightMatch(text, query) {
            if (!query || !text) return text;
            const terms = query.toLowerCase().trim().split(/\s+/).filter(t => t.length > 0);
            if (terms.length === 0) return text;
            
            // Single regex pass for efficiency
            const pattern = terms.map(t => this.escapeRegExp(t)).join('|');
            const regex = new RegExp(`(${pattern})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        },

        escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },
        
        // Search services
        searchServices(query) {
            const q = query.toLowerCase().trim();
            const resultsContainer = this.elements.searchResults;
            
            let filtered = [];
            if (!q) {
                filtered = this.services.slice(0, 40);
            } else {
                const terms = q.split(/\s+/).filter(t => t.length > 0);
                
                filtered = this.services.filter(s => {
                    const idMatch = s.id.toString() === q;
                    if (idMatch) return true;
                    
                    const name = s.name.toLowerCase();
                    const category = s.category.toLowerCase();
                    const idStr = s.id.toString();
                    
                    return terms.every(term => 
                        name.includes(term) || 
                        category.includes(term) || 
                        idStr.includes(term)
                    );
                });
                
                filtered.sort((a, b) => {
                    const aId = a.id.toString();
                    const bId = b.id.toString();
                    if (aId === q) return -1;
                    if (bId === q) return 1;
                    
                    const aName = a.name.toLowerCase();
                    const bName = b.name.toLowerCase();
                    const aStart = aName.startsWith(q);
                    const bStart = bName.startsWith(q);
                    
                    if (aStart && !bStart) return -1;
                    if (!aStart && bStart) return 1;
                    
                    return 0;
                });
            }
            
            const displayResults = filtered.slice(0, 50);
            
            if (displayResults.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="search-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="opacity-20 mb-4">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <div class="search-empty-title">No matches found</div>
                        <div class="search-empty-text">Try searching by Service ID or Name</div>
                    </div>
                `;
                return;
            }
            
            resultsContainer.innerHTML = displayResults.map(svc => {
                const etbRate = (svc.rate * this.rateMultiplier * (1 - this.discountPercent/100)).toFixed(2);
                const highlightedName = this.highlightMatch(svc.name, q);
                const highlightedCategory = this.highlightMatch(svc.category, q);
                const highlightedId = this.highlightMatch(svc.id.toString(), q);
                
                return `
                    <div class="search-result-item" data-service-id="${svc.id}" data-category="${svc.category}">
                        <div class="flex justify-between items-start gap-2 mb-1">
                            <span class="text-[9px] font-mono bg-smm-accent/15 text-smm-accent px-1.5 py-0.5 rounded shrink-0 font-bold">#${highlightedId}</span>
                            <div class="search-result-category text-right truncate text-[10px] opacity-70 uppercase font-black tracking-widest">${highlightedCategory}</div>
                        </div>
                        <div class="search-result-name font-bold text-[13.5px] leading-tight text-white mb-1.5">${highlightedName}</div>
                        <div class="bg-black/20 p-2 rounded-lg">
                            <div class="flex justify-between items-center text-[10px] text-gray-400 mb-1">
                                <span>Min: ${svc.min.toLocaleString()}</span>
                                <span>Max: ${svc.max.toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between items-end">
                                ${svc.averageTime ? 
                                    `<span class="text-[10px] text-blue-400 font-bold bg-blue-500/10 px-1.5 py-0.5 rounded">🕒 ${svc.averageTime}</span>` : 
                                    '<span class="text-[10px] font-mono opacity-30">FAST START</span>'}
                                <div class="text-[12px] font-black text-smm-accent">${this.formatETB(svc.rate * this.rateMultiplier * (1 - this.discountPercent/100))} / 1K</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
                item.addEventListener('click', () => {
                    const id = item.dataset.serviceId;
                    const cat = item.dataset.category;
                    const service = this.services.find(s => s.id == id);
                    
                    this.closeSearchModal();
                    
                    // 1. Select Platform (and switch tab)
                    this.selectPlatformByService(cat);
                    
                    // 2. Select Category (updates UI text)
                    this.selectCategory(cat);
                    
                    // 3. Select Service (updates UI text, inputs, scrolls)
                    this.selectService(service);
                    
                    // Force scroll to order form for search results (overriding any smoothScroll checks)
                    setTimeout(() => {
                         document.querySelector('.order-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 600);
                });
            });
        },
        
        // Show toast notification
        showToast(message, type = 'error') {
            this.elements.toast.className = `toast ${type} visible`;
            this.elements.toastMessage.textContent = message;
            
            // Update icon based on type
            const iconSvg = type === 'success' 
                ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
                : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
            this.elements.toast.querySelector('.toast-icon').innerHTML = iconSvg;
            
            setTimeout(() => {
                this.elements.toast.classList.remove('visible');
            }, 3000);
        },
        
        // Show/hide loading overlay
        showLoading(show) {
            this.elements.loadingOverlay.classList.toggle('visible', show);
        },
        
        // Success celebration with confetti effect
        showSuccessCelebration() {
            // Create confetti element
            const confetti = document.createElement('div');
            confetti.className = 'success-confetti';
            document.body.appendChild(confetti);
            
            // Remove after animation
            setTimeout(() => confetti.remove(), 1000);
            
            // Add haptic feedback if available (mobile)
            if (navigator.vibrate) {
                navigator.vibrate([50, 30, 50]);
            }
        },
        
        // Activate CTA pulse when form is ready
        activateCTA() {
            const btn = this.elements.orderBtn;
            if (btn && !btn.disabled) {
                btn.classList.add('cta-ready');
                setTimeout(() => btn.classList.remove('cta-ready'), 4000);
            }
        },

        
        renderActionButtons(order) {
            // Find service details to check permissions
            const service = this.services.find(s => s.id == (order.service_id || order.service));
            
            // Strict checks: handle booleans, strings, and numeric forms
            const check = (val) => val === true || val === 'true' || val === 'ok' || val === 1 || val === '1' || val === 'Yes';
            const canRefill = check(service?.refill);
            let btns = '';
            
            // Refill Logic: Only show if service supports it AND order is completed
            if (canRefill && order.status === 'completed') {
                const now = new Date();
                const createdAt = new Date(order.created_at);
                const diffMs = now - createdAt;
                const msIn24h = 24 * 60 * 60 * 1000;
                
                if (diffMs >= msIn24h) {
                    btns += `<button onclick="SMM.refillOrder(${order.id})" class="text-[10px] bg-green-500/10 text-green-400 border border-green-500/20 px-2 py-1 rounded hover:bg-green-500/20 transition-colors">Refill</button>`;
                } else {
                    const remainingMs = msIn24h - diffMs;
                    const hours = Math.floor(remainingMs / (60 * 60 * 1000));
                    const minutes = Math.floor((remainingMs % (60 * 60 * 1000)) / (60 * 1000));
                    const timeStr = `${hours}h ${minutes}m`;
                    // Improve mobile UX by showing time in a toast instead of a title they can't see easily
                    btns += `<button class="text-[10px] bg-gray-500/10 text-gray-400 border border-gray-500/10 px-2 py-1 rounded opacity-50" onclick="event.stopPropagation(); SMM.showToast('Refill available in ${timeStr}', 'info')">Refill</button>`;
                }
            }
            
            return btns || '<span class="text-xs text-gray-600">-</span>';
        },

        async refillOrder(id) {
            if(!confirm('Request a refill for this order?')) return;
            this.showLoading(true);
            try {
                const res = await fetch('user_actions.php', {
                    method: 'POST', 
                    body: JSON.stringify({action:'refill', order_id:id})
                });
                const data = await res.json();
                if(data.success) {
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.error || 'Refill failed', 'error');
                }
            } catch(e) { console.error(e); }
            this.showLoading(false);
        }
    };
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => SMM.init());
    </script>
    <script src="https://js.chapa.co/v1/inline.js"></script>
    </div><!-- Close .tg-scroll-wrapper -->
</body>
</html>
