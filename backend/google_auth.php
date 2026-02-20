<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Google OAuth 2.0 Authentication Handler
 * Handles the OAuth flow for website users (non-Telegram)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
    session_start();
}

// Check for cURL extension
if (!function_exists('curl_init')) {
    die('cURL extension is required for Google Authentication. Please enable it in your PHP configuration.');
}

require_once 'config_google.php';
require_once 'db.php';
require_once 'utils_bot.php';

header('Content-Type: text/html; charset=utf-8');

// For debugging 500 errors - will show the actual error message
error_reporting(E_ALL);
ini_set('display_errors', 1);


/**
 * Generate a random state token for CSRF protection
 */
function generateStateToken() {
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;
    return $state;
}

/**
 * Verify the state token
 */
function verifyStateToken($state) {
    if (!isset($_SESSION['google_oauth_state'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['google_oauth_state'], $state);
    unset($_SESSION['google_oauth_state']);
    return $valid;
}

/**
 * Build the Google OAuth authorization URL
 */
function getGoogleAuthUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => GOOGLE_SCOPES,
        'state' => generateStateToken(),
        'access_type' => 'offline',
        'prompt' => 'select_account'
    ];
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Exchange authorization code for access token
 */
function exchangeCodeForToken($code) {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'code' => $code,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Get user info from Google using access token
 */
function getGoogleUserInfo($accessToken) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GOOGLE_USERINFO_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Create or update user in database with Google profile
 */
function ensureGoogleUserExists($googleId, $email, $firstName = null, $lastName = null, $photoUrl = null) {
    global $conn;
    
    if (!$conn) {
        error_log("Google Auth Error: Database connection \$conn is not defined or null.");
        return null;
    }

    
    // Sanitize inputs
    $googleId = db_escape($googleId);
    $email = db_escape($email);
    $firstName = $firstName ? db_escape($firstName) : 'User';
    $lastName = $lastName ? db_escape($lastName) : '';
    $photoUrl = $photoUrl ? db_escape($photoUrl) : null;
    
    // Check if user exists by google_id first
    $result = mysqli_query($conn, "SELECT * FROM auth WHERE google_id = '$googleId' LIMIT 1");
    $existing = mysqli_fetch_assoc($result);
    
    if (!$existing) {
        // Check if user exists by email (might have signed up differently)
        $result = mysqli_query($conn, "SELECT * FROM auth WHERE email = '$email' LIMIT 1");
        $existing = mysqli_fetch_assoc($result);
    }
    
    if (!$existing) {
        // Generate a unique internal user ID for Google users (negative to avoid collision with Telegram IDs)
        // Generate a unique internal user ID for Google users
        // Use a value that fits within a signed 32-bit integer range (-2,147,483,648 to 2,147,483,647)
        // We use negative IDs to avoid collisions with Telegram's positive IDs
        $internalId = - (abs(crc32($googleId)) % 1000000000 + 1000000000); 

        
        // Make sure ID is unique
        $checkExisting = db('select', 'auth', 'tg_id', $internalId, 'tg_id');
        while ($checkExisting) {
            $internalId--;
            $checkExisting = db('select', 'auth', 'tg_id', $internalId, 'tg_id');
        }
        
        // Create new user
        $sql = "INSERT INTO auth (tg_id, google_id, email, first_name, last_name, photo_url, balance, auth_provider, created_at, last_login, last_seen) 
                VALUES ('$internalId', '$googleId', '$email', '$firstName', '$lastName', " . ($photoUrl ? "'$photoUrl'" : "NULL") . ", 0, 'google', NOW(), NOW(), NOW())";
        
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            error_log("Google Auth Error: " . mysqli_error($conn));
            return null;
        }
        
        // Welcome notification
        $welcome_msg = "Welcome to Paxyo!\nYour account has been created with Google Sign-In. If you need help, contact support at t.me/paxyo";
        mysqli_query($conn, "INSERT INTO user_alerts (user_id, message, is_read) VALUES ('$internalId', '" . db_escape($welcome_msg) . "', 0)");
        
        // Notify admin
        notify_bot_admin([
            'type' => 'newuser',
            'uid' => $internalId,
            'uuid' => $firstName . ' (Google)',
            'email' => $email
        ]);
        
        return [
            'status' => 'new',
            'user_id' => $internalId
        ];
    } else {
        // Update existing user with Google info
        $userId = $existing['tg_id'];
        $updates = [
            "last_login = NOW()",
            "last_seen = NOW()"
        ];
        
        if (empty($existing['google_id'])) {
            $updates[] = "google_id = '$googleId'";
        }
        if (empty($existing['email'])) {
            $updates[] = "email = '$email'";
        }
        if (empty($existing['auth_provider'])) {
            $updates[] = "auth_provider = 'google'";
        }
        if ($firstName && $firstName !== 'User') {
            $updates[] = "first_name = '$firstName'";
        }
        if ($lastName) {
            $updates[] = "last_name = '$lastName'";
        }
        if ($photoUrl && empty($existing['photo_url'])) {
            $updates[] = "photo_url = '$photoUrl'";
        }
        
        $updateStr = implode(', ', $updates);
        mysqli_query($conn, "UPDATE auth SET $updateStr WHERE tg_id = '$userId'");
        
        return [
            'status' => 'existing',
            'user_id' => $userId
        ];
    }
}

/**
 * Show error page with nice styling
 */
function showError($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Authentication Error - Paxyo</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                background: linear-gradient(135deg, #0a0a0f 0%, #1a1a24 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                color: white;
                padding: 20px;
            }
            .error-card {
                background: rgba(26, 26, 36, 0.95);
                border: 1px solid rgba(255, 71, 87, 0.3);
                border-radius: 16px;
                padding: 40px;
                max-width: 400px;
                text-align: center;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
            .error-icon {
                width: 64px;
                height: 64px;
                background: rgba(255, 71, 87, 0.1);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
            }
            .error-icon svg {
                width: 32px;
                height: 32px;
                color: #ff4757;
            }
            h1 {
                font-size: 24px;
                margin-bottom: 12px;
                color: #ff4757;
            }
            p {
                color: #a0aec0;
                margin-bottom: 24px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
                color: white;
                padding: 12px 32px;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 600;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(108, 92, 231, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h1>Authentication Error</h1>
            <p><?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type"); echo htmlspecialchars($message); ?></p>
            <a href="login.php" class="btn">Try Again</a>
        </div>
    </body>
    </html>
    <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
    exit;
}

// ============================================
// MAIN FLOW
// ============================================

// If 'action=login' or no params, redirect to Google
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    header('Location: ' . getGoogleAuthUrl());
    exit;
}

// Handle callback from Google
if (isset($_GET['code'])) {
    // Verify state token
    if (!isset($_GET['state']) || !verifyStateToken($_GET['state'])) {
        showError('Invalid state token. Please try again.');
    }
    
    // Exchange code for token
    $tokenData = exchangeCodeForToken($_GET['code']);
    
    if (!$tokenData || !isset($tokenData['access_token'])) {
        showError('Failed to get access token from Google.');
    }
    
    // Get user info
    $userInfo = getGoogleUserInfo($tokenData['access_token']);
    
    if (!$userInfo || !isset($userInfo['id'])) {
        showError('Failed to get user information from Google.');
    }
    
    // Create/update user in database
    $result = ensureGoogleUserExists(
        $userInfo['id'],
        $userInfo['email'] ?? '',
        $userInfo['given_name'] ?? 'User',
        $userInfo['family_name'] ?? '',
        $userInfo['picture'] ?? null
    );
    
    if (!$result) {
        showError('Failed to create user account. Please try again.');
    }
    
    // Set session variables
    $_SESSION['tg_id'] = $result['user_id'];
    $_SESSION['tg_first_name'] = $userInfo['given_name'] ?? 'User';
    $_SESSION['tg_last_name'] = $userInfo['family_name'] ?? '';
    $_SESSION['tg_photo_url'] = $userInfo['picture'] ?? null;
    $_SESSION['auth_provider'] = 'google';
    $_SESSION['google_email'] = $userInfo['email'] ?? '';
    
    // Set cookie for persistence
    setcookie("id", $result['user_id'], time() + 86400 * 30, "/", "", true, true);
    
    // Redirect to main app
    header('Location: index.php');
    exit;
}

// Handle error from Google
if (isset($_GET['error'])) {
    $errorMsg = 'Google authentication was cancelled or failed.';
    if ($_GET['error'] === 'access_denied') {
        $errorMsg = 'You denied access to your Google account.';
    }
    showError($errorMsg);
}

// No action - show login page or redirect to login
header('Location: login.php');
exit;
?>
