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

$stmt = $pdo->prepare("SELECT * FROM user_alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$tgId]);
$alerts = $stmt->fetchAll();

$result = [];
$unreadCount = 0;
foreach ($alerts as $a) {
    $result[] = [
        'id' => (int)$a['id'],
        'message' => $a['message'],
        'is_read' => (bool)$a['is_read'],
        'created_at' => $a['created_at']
    ];
    if (!$a['is_read']) $unreadCount++;
}

echo json_encode([
    'alerts' => $result,
    'unread_count' => $unreadCount
]);
