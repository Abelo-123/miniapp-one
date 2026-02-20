<?php
/**
 * Chapa Checkout - Redirect URL Generator
 * Generates a payment link that user can be redirected to
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$amount = floatval($input['amount'] ?? 0);
$user_id = $input['user_id'] ?? 111;
$user_name = $input['user_name'] ?? 'User';

if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

$tx_ref = 'paxyo-' . $user_id . '-' . time();

// Build Chapa payment link with public key (works for redirect checkout)
$chapa_url = 'https://checkout.chapa.co/?' . http_build_query([
    'amount' => $amount,
    'currency' => 'ETB',
    'email' => "user-{$user_id}@telegram.com",
    'first_name' => $user_name,
    'last_name' => 'User',
    'tx_ref' => $tx_ref,
    'callback_url' => 'http://localhost/paxyo/webhook_handler.php',
    'return_url' => 'http://localhost:5178/miniapp-one/?deposit_complete=1&ref=' . $tx_ref . '&amount=' . $amount,
    'customization[title]' => 'Paxyo SMM Deposit',
    'customization[description]' => 'Add funds to your SMM panel account',
    'public_key' => 'CHAPUBK-s9JQu74c7hAcdPPGxaAF6aT22Ih4HNtm'
]);

echo json_encode([
    'success' => true,
    'checkout_url' => $chapa_url,
    'tx_ref' => $tx_ref
]);
?>
