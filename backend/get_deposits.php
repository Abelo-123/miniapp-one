<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header('Content-Type: application/json');
require_once 'config/database.php';

$tgId = $_GET['tg_id'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$tgId]);
$deposits = $stmt->fetchAll();

$result = [];
foreach ($deposits as $d) {
    $result[] = [
        'id' => (int)$d['id'],
        'amount' => floatval($d['amount']),
        'reference_id' => $d['reference_id'],
        'status' => $d['status'],
        'created_at' => $d['created_at']
    ];
}

echo json_encode($result);
