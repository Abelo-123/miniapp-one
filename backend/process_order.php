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
$serviceId = $input['service'] ?? null;
$link = $input['link'] ?? '';
$quantity = $input['quantity'] ?? 0;
$tgId = $input['tg_id'] ?? 1;

if (!$serviceId || !$link || !$quantity) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$apiKey = '7aed775ad8b88b50a1706db2f35c5eaf';

$cacheFile = __DIR__ . '/cache/services.json';
$services = [];
if (file_exists($cacheFile)) {
    $services = json_decode(file_get_contents($cacheFile), true);
}

$service = null;
foreach ($services as $s) {
    if ($s['service'] == $serviceId) {
        $service = $s;
        break;
    }
}

if (!$service) {
    echo json_encode(['success' => false, 'error' => 'Service not found']);
    exit;
}

$rate = floatval($service['rate']);
$charge = ($rate / 1000) * $quantity;

$stmt = $pdo->prepare("SELECT * FROM auth WHERE tg_id = ?");
$stmt->execute([$tgId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

if ($user['balance'] < $charge) {
    echo json_encode(['success' => false, 'error' => 'Insufficient balance']);
    exit;
}

$apiUrl = "https://godofpanel.com/api/v2?key=$apiKey&action=add&service=$serviceId&link=" . urlencode($link) . "&quantity=$quantity";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$apiResponse = curl_exec($ch);
curl_close($ch);

$apiResult = json_decode($apiResponse, true);

if (isset($apiResult['error'])) {
    echo json_encode(['success' => false, 'error' => $apiResult['error']]);
    exit;
}

if (!isset($apiResult['order'])) {
    echo json_encode(['success' => false, 'error' => 'API order failed']);
    exit;
}

$apiOrderId = $apiResult['order'];

$newBalance = $user['balance'] - $charge;
$stmt = $pdo->prepare("UPDATE auth SET balance = ? WHERE tg_id = ?");
$stmt->execute([$newBalance, $tgId]);

$stmt = $pdo->prepare("INSERT INTO orders (user_id, api_order_id, service_id, service_name, link, quantity, charge, status, remains, start_count, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, 0, NOW())");
$stmt->execute([$tgId, $apiOrderId, $serviceId, $service['name'], $link, $quantity, $charge, $quantity]);

echo json_encode([
    'success' => true,
    'order_id' => $apiOrderId,
    'new_balance' => $newBalance
]);
