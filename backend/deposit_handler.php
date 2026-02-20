<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db.php';
include 'utils_bot.php';

// Session handling to match smm.php
session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? null;

// Fallback for testing/dev environment (matches smm.php)
if (!$user_id) {
    // Check if we allow hardcoded fallback for dev
    // In production, you would remove this
    $user_id = 111;
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$amount = floatval($input['amount'] ?? 0);
$reference_id = $input['reference_id'] ?? '';

if ($amount <= 0 || empty($reference_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount or reference ID']);
    exit;
}

// TODO: Verify transaction with Chapa API using Secret Key
// $chapa_secret = 'CHASECK-...'; 
// verify_payment($reference_id, $chapa_secret) ...

// 1. Record Deposit
$deposit_data = [
    'user_id' => $user_id,
    'amount' => $amount,
    'reference_id' => $reference_id,
    'status' => 'completed'
];

// Check if reference_id already exists to prevent double counting
$exists = db('select', 'deposits', 'reference_id', $reference_id);
if ($exists) {
    echo json_encode(['status' => 'error', 'message' => 'Transaction already processed']);
    exit;
}

db('insert', 'deposits', $deposit_data);

// 2. Update User Balance safely
$user_id_safe = db_escape($user_id);
$amount_safe = db_escape($amount);
$sql = "UPDATE auth SET balance = balance + $amount_safe WHERE tg_id = '$user_id_safe'";
if (mysqli_query($conn, $sql)) {
    // 3. Get new balance
    $new_balance = db('select', 'auth', 'tg_id', $user_id, 'balance');
    echo json_encode(['status' => 'success', 'new_balance' => $new_balance, 'message' => 'Deposit successful']);

    // Notify Bot Admin
    $first_name = db('select', 'auth', 'tg_id', $user_id, 'first_name') ?? ('User ' . substr($user_id, -4));
    notify_bot_admin([
        'type' => 'deposit',
        'uid' => $user_id,
        'uuid' => $first_name,
        'amount' => $amount
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error updating balance']);
}
?>
