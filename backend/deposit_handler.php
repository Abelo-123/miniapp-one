<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header('Content-Type: application/json');
require_once 'config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$amount = floatval($input['amount'] ?? 0);
$referenceId = $input['reference_id'] ?? '';
$tgId = $input['tg_id'] ?? 1;

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'error' => 'Invalid amount']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO deposits (user_id, amount, reference_id, status, created_at) VALUES (?, ?, ?, 'completed', NOW())");
$stmt->execute([$tgId, $amount, $referenceId]);

$stmt = $pdo->prepare("UPDATE auth SET balance = balance + ? WHERE tg_id = ?");
$stmt->execute([$amount, $tgId]);

$stmt = $pdo->prepare("SELECT balance FROM auth WHERE tg_id = ?");
$stmt->execute([$tgId]);
$newBalance = $stmt->fetchColumn();

echo json_encode([
    'status' => 'completed',
    'new_balance' => floatval($newBalance)
]);
