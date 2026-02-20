<?php
/**
 * Telegram Auth API - telegram_auth.php
 * 
 * Validates Telegram initData via HMAC-SHA256 signature,
 * creates or updates user in the database, and establishes a session.
 * 
 * Method: POST
 * Request:  { "initData": "<telegram_initData_string>" }
 * Response: { "success": true, "user": { ... } }
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Session setup for Telegram iframe (SameSite=None required)
session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';
include 'utils_bot.php';

// ---------------------------------------------------
// CONFIGURATION - Replace with your actual bot token
// ---------------------------------------------------
$BOT_TOKEN = getenv('TELEGRAM_BOT_TOKEN') ?: 'YOUR_TELEGRAM_BOT_TOKEN';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$initData = $input['initData'] ?? '';

if (empty($initData)) {
    echo json_encode(['error' => 'initData is required']);
    exit();
}

// ---------------------------------------------------
// STEP 1: Parse initData (URL-encoded key-value pairs)
// ---------------------------------------------------
$params = [];
parse_str($initData, $params);

// Extract and remove the hash
$received_hash = $params['hash'] ?? '';
unset($params['hash']);

if (empty($received_hash)) {
    echo json_encode(['error' => 'Hash not found in initData']);
    exit();
}

// ---------------------------------------------------
// STEP 2: Sort remaining params alphabetically by key
// ---------------------------------------------------
ksort($params);

// ---------------------------------------------------
// STEP 3: Create data_check_string (join with \n)
// ---------------------------------------------------
$data_check_parts = [];
foreach ($params as $key => $value) {
    $data_check_parts[] = "$key=$value";
}
$data_check_string = implode("\n", $data_check_parts);

// ---------------------------------------------------
// STEP 4: Compute HMAC-SHA256 signature
// ---------------------------------------------------
// secret_key = HMAC-SHA256("WebAppData", BOT_TOKEN)
$secret_key = hash_hmac('sha256', $BOT_TOKEN, 'WebAppData', true);

// computed_hash = HMAC-SHA256(data_check_string, secret_key)
$computed_hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));

// ---------------------------------------------------
// STEP 5: Compare hashes
// ---------------------------------------------------
if (!hash_equals($computed_hash, $received_hash)) {
    echo json_encode(['error' => 'Invalid initData signature']);
    exit();
}

// ---------------------------------------------------
// STEP 6: Check auth_date expiry (86400 seconds = 24 hours)
// ---------------------------------------------------
$auth_date = intval($params['auth_date'] ?? 0);
if ($auth_date === 0) {
    echo json_encode(['error' => 'Missing auth_date']);
    exit();
}

$now = time();
$max_age = 86400; // 24 hours
if (($now - $auth_date) > $max_age) {
    echo json_encode(['error' => 'initData has expired']);
    exit();
}

// ---------------------------------------------------
// STEP 7: Extract user data from initData
// ---------------------------------------------------
$user_data = json_decode($params['user'] ?? '{}', true);

if (empty($user_data) || !isset($user_data['id'])) {
    echo json_encode(['error' => 'User data not found in initData']);
    exit();
}

$tg_id = intval($user_data['id']);
$first_name = $user_data['first_name'] ?? 'User';
$last_name = $user_data['last_name'] ?? '';
$photo_url = $user_data['photo_url'] ?? '';

// Build display name
$display_name = trim("$first_name $last_name") ?: "User $tg_id";

// ---------------------------------------------------
// STEP 8: Create or Update user in database
// ---------------------------------------------------
$existing_user = db('select', 'auth', 'tg_id', $tg_id);

if ($existing_user) {
    // Update existing user profile
    $update_data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'last_login' => date('Y-m-d H:i:s')
    ];
    
    // Only update photo_url if provided
    if (!empty($photo_url)) {
        $update_data['photo_url'] = $photo_url;
    }
    
    db('update', 'auth', 'tg_id', $tg_id, $update_data);
    
    // Refresh user data
    $user = db('select', 'auth', 'tg_id', $tg_id);
} else {
    // NEW USER - Create account
    $new_user_data = [
        'tg_id' => $tg_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'photo_url' => $photo_url,
        'balance' => 0.00,
        'is_blocked' => 0,
        'auth_provider' => 'telegram',
        'last_login' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    db('insert', 'auth', $new_user_data);
    
    // Send welcome alert
    db('insert', 'user_alerts', [
        'user_id' => $tg_id,
        'message' => "🎉 Welcome to Paxyo SMM, $first_name! Your account has been created successfully.",
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Notify admin about new user
    notify_bot_admin([
        'type' => 'newuser',
        'uid' => $tg_id,
        'uuid' => $display_name
    ]);
    
    $user = db('select', 'auth', 'tg_id', $tg_id);
}

// ---------------------------------------------------
// STEP 9: Set session variables
// ---------------------------------------------------
$_SESSION['tg_id'] = $tg_id;
$_SESSION['tg_first_name'] = $first_name;
$_SESSION['tg_photo_url'] = $user['photo_url'] ?? $photo_url;

// ---------------------------------------------------
// STEP 10: Return success response
// ---------------------------------------------------
echo json_encode([
    'success' => true,
    'user' => [
        'id' => intval($user['tg_id']),
        'first_name' => $user['first_name'],
        'display_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        'photo_url' => $user['photo_url'] ?? '',
        'balance' => floatval($user['balance'] ?? 0)
    ]
]);
?>
