<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// user_actions.php - Handle Cancel and Refill
session_start();
include 'db.php';

header('Content-Type: application/json');

// Auth Check
if (!isset($_SESSION['tg_id']) && !isset($_COOKIE['id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'];

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$order_id = intval($input['order_id'] ?? 0);

if (!$order_id) {
    echo json_encode(['error' => 'Invalid Order ID']);
    exit;
}

// Get Order Details
$query = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
$result = mysqli_query($conn, $query);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$api_order_id = $order['api_order_id'];
$apiKey = 'YOUR_GODOFPANEL_API_KEY';
$apiUrl = "https://godofpanel.com/api/v2";

// --- REFILL ORDER ---
if ($action === 'refill') {
    // Check if service supports refill
    $service_id = $order['service_id'];
    $cacheFile = __DIR__ . '/cache/services.json';
    $can_refill = false;

    if (file_exists($cacheFile)) {
        $services = json_decode(file_get_contents($cacheFile), true);
        if (is_array($services)) {
            foreach ($services as $svc) {
                if ($svc['service'] == $service_id) {
                    $refill_val = $svc['refill'] ?? false;
                    $can_refill = ($refill_val === true || $refill_val === 'true' || $refill_val === 1 || $refill_val === '1' || strtolower($refill_val) === 'yes' || strtolower($refill_val) === 'ok');
                    break;
                }
            }
        }
    }

    if (!$can_refill) {
        echo json_encode(['error' => 'This service does not support refill.']);
        exit;
    }

    // Call API
    $postData = [
        'key' => $apiKey,
        'action' => 'refill',
        'order' => $api_order_id
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($response, true);

    if (isset($res['refill'])) {
         // Success
         // Optionally update DB to show "Refill Pending"
         require_once 'utils_bot.php';
         notify_bot_admin([
             'type' => 'refill',
             'uid' => $user_id,
             'order' => $order_id,
             'uuid' => $res['refill'],
             'service' => 'Service #' . $service_id // Fallback as we don't have name readily available without extra query
         ]);
         
         echo json_encode(['success' => true, 'message' => 'Refill request sent! ID: ' . $res['refill']]);
    } else {
        $err = $res['error'] ?? 'Refill not available';
        echo json_encode(['error' => $err]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>
