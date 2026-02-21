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

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$tgId]);
$orders = $stmt->fetchAll();

$result = [];
foreach ($orders as $o) {
    $result[] = [
        'id' => (int)$o['api_order_id'],
        'api_order_id' => (int)$o['api_order_id'],
        'service_id' => (int)$o['service_id'],
        'service_name' => $o['service_name'],
        'link' => $o['link'],
        'quantity' => (int)$o['quantity'],
        'charge' => (float)$o['charge'],
        'status' => $o['status'],
        'remains' => (int)$o['remains'],
        'start_count' => (int)$o['start_count'],
        'created_at' => $o['created_at']
    ];
}

echo json_encode(['orders' => $result]);
