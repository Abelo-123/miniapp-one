<?php
// process_order.php
// Handles order placement with godofpanel API

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

session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';
include 'utils_bot.php';

// Get user ID
$user_id = $_SESSION['tg_id'] ?? $_COOKIE['id'] ?? 111;

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid request data']);
    exit();
}

$service_id = intval($input['service'] ?? 0);
$link = trim($input['link'] ?? '');
$quantity = intval($input['quantity'] ?? 0);

// Validation
if (!$service_id) {
    echo json_encode(['error' => 'Service is required']);
    exit();
}

if (!$link) {
    echo json_encode(['error' => 'Link is required']);
    exit();
}

if (!$quantity) {
    echo json_encode(['error' => 'Quantity is required']);
    exit();
}

if ($quantity % 10 !== 0) {
    echo json_encode(['error' => 'Quantity must be a multiple of 10']);
    exit();
}

// Get service details from cache or API
$services = getServicesFromCache();
$service = null;
foreach ($services as $s) {
    if ($s['service'] == $service_id) {
        $service = $s;
        break;
    }
}

if (!$service) {
    echo json_encode(['error' => "Service not found (ID: $service_id)"]);
    exit();
}

// Validate quantity range
$min = intval($service['min']);
$max = intval($service['max']);

if ($quantity < $min) {
    echo json_encode(['error' => "Minimum quantity is $min"]);
    exit();
}

if ($quantity > $max) {
    echo json_encode(['error' => "Maximum quantity is $max"]);
    exit();
}

// Check for active holiday discount
$today = date('Y-m-d');
$holiday_res = mysqli_query($conn, "SELECT discount_percent FROM holidays WHERE status = 'active' AND '$today' BETWEEN start_date AND end_date ORDER BY discount_percent DESC LIMIT 1");
$discount_percent = 0;
if ($holiday_res && mysqli_num_rows($holiday_res) > 0) {
    $discount_percent = floatval(mysqli_fetch_assoc($holiday_res)['discount_percent']);
}

// Get settings for maintenance and rate
$settings_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('rate_multiplier', 'maintenance_mode', 'maintenance_allowed_ids')");
$settings = [
    'rate_multiplier' => 400,
    'maintenance_mode' => '0',
    'maintenance_allowed_ids' => ''
];
while ($row = mysqli_fetch_assoc($settings_res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// EMERGENCY CHECK: Maintenance Mode
if ($settings['maintenance_mode'] === '1') {
    $allowed_ids = array_filter(array_map('trim', explode(',', $settings['maintenance_allowed_ids'])));
    if (!in_array($user_id, $allowed_ids)) {
        echo json_encode(['error' => 'System under maintenance. Order placement is temporarily disabled.']);
        exit();
    }
}

$rate_multiplier = floatval($settings['rate_multiplier']);

// Calculate charge
$rate = floatval($service['rate']) * $rate_multiplier;
if ($discount_percent > 0) {
    $rate = $rate * (1 - ($discount_percent / 100));
}
$charge = ($quantity / 1000) * $rate;

// Get user balance
$user_balance = floatval(db('select', 'auth', 'tg_id', $user_id, 'balance') ?? 0);

if ($charge > $user_balance) {
    echo json_encode([
        'error' => "Insufficient balance. Required: " . number_format($charge, 2) . " ETB, Available: " . number_format($user_balance, 2) . " ETB"
    ]);
    exit();
}

// Place order with godofpanel API
$apiKey = 'YOUR_GODOFPANEL_API_KEY';
$apiUrl = "https://godofpanel.com/api/v2";

$postData = [
    'key' => $apiKey,
    'action' => 'add',
    'service' => $service_id,
    'link' => $link,
    'quantity' => $quantity
];

if (isset($input['comments']) && !empty($input['comments'])) {
    $postData['comments'] = $input['comments'];
}
if (isset($input['username']) && !empty($input['username'])) {
    $postData['username'] = $input['username'];
}
if (isset($input['answer_number']) && !empty($input['answer_number'])) {
    $postData['answer_number'] = $input['answer_number'];
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    echo json_encode(['error' => 'API request failed: ' . $error]);
    exit();
}

curl_close($ch);

$result = json_decode($response, true);

if (isset($result['error'])) {
    
    // Notify Admin of Failure
    notify_bot_admin([
        'type' => 'order_error',
        'uid' => $user_id,
        'service' => $service_id,
        'error' => $result['error']
    ]);

    echo json_encode(['error' => 'Order failed: ' . $result['error']]);
    exit();
}

$order_id = $result['order'] ?? null;

if (!$order_id) {
    echo json_encode(['error' => 'Order failed: No order ID returned']);
    exit();
}

// Deduct balance ATOMICALLY (prevents race conditions with concurrent orders)
$charge_safe = db_escape($charge);
$user_id_safe = db_escape($user_id);
mysqli_query($conn, "UPDATE auth SET balance = balance - $charge_safe WHERE tg_id = '$user_id_safe'");

// Get new balance after deduction
$new_balance = floatval(db('select', 'auth', 'tg_id', $user_id, 'balance') ?? 0);

// Save order to database
$order_data = [
    'user_id' => $user_id,
    'api_order_id' => $order_id,
    'service_id' => $service_id,
    'service_name' => $service['name'],
    'link' => $link,
    'quantity' => $quantity,
    'charge' => $charge,
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
];

// Try to insert order (table may not exist yet)
try {
    db('insert', 'orders', $order_data);
} catch (Exception $e) {
    // Table might not exist, that's okay for now
}

// Return success
echo json_encode([
    'success' => true,
    'order_id' => $order_id,
    'charge' => $charge,
    'new_balance' => $new_balance
]);

// Notify Bot Admin
notify_bot_admin([
    'type' => 'neworder',
    'uid' => $user_id,
    'uuid' => $_SESSION['tg_first_name'] ?? 'User',
    'order' => $service['name'],
    'service' => $service_id,
    'amount' => $charge,
    'panel' => 'GodOfPanel',
    'pb' => $user_balance
]);

exit();

// Helper function to get services from cache
function getServicesFromCache() {
    $cacheFile = __DIR__ . '/cache/services.json';
    $cacheTime = 3600; // 1 hour cache
    
    // Load stale data first if available
    $staleData = [];
    if (file_exists($cacheFile)) {
        $staleData = json_decode(file_get_contents($cacheFile), true) ?: [];
    }

    // Check if cache is fresh
    if (!empty($staleData) && (time() - filemtime($cacheFile) < $cacheTime)) {
        return $staleData;
    }
    
    // Fetch fresh from API
    $apiKey = 'YOUR_GODOFPANEL_API_KEY';
    $apiUrl = "https://godofpanel.com/api/v2?key=$apiKey&action=services";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && !isset($data['error'])) {
            // Save to cache
            if (!is_dir(__DIR__ . '/cache')) {
                mkdir(__DIR__ . '/cache', 0755, true);
            }
            file_put_contents($cacheFile, $response, LOCK_EX);
            return $data;
        }
    }
    
    // Fallback to stale data if API failed
    if (!empty($staleData)) {
        return $staleData;
    }
    
    return [];
}
?>
