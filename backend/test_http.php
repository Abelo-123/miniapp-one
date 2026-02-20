<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * HTTP Integration Test - Tests all endpoints via HTTP
 * Run: D:\next\xampp\php\php.exe test_http.php
 * Requires: PHP built-in server running on localhost:8888
 */

$BASE = 'http://localhost:8888';

echo "\n" . str_repeat("=", 60) . "\n";
echo "  PAXYO SMM - HTTP ENDPOINT TESTS\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

$passed = 0;
$failed = 0;

function http_test($name, $method, $url, $body = null, $expect_key = null) {
    global $passed, $failed, $BASE;
    
    $ch = curl_init($BASE . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        $failed++;
        echo "  [FAIL] $name - cURL error: $error\n";
        return null;
    }
    
    $data = json_decode($response, true);
    $ok = $code >= 200 && $code < 400;
    
    if ($expect_key && $data) {
        $ok = $ok && isset($data[$expect_key]);
    }
    
    if ($ok) {
        $passed++;
        $preview = substr($response, 0, 80);
        echo "  [PASS] $name (HTTP $code) → $preview" . (strlen($response) > 80 ? '...' : '') . "\n";
    } else {
        $failed++;
        echo "  [FAIL] $name (HTTP $code) → " . substr($response, 0, 120) . "\n";
    }
    
    return $data;
}

// 1. Heartbeat
echo "--- GET Endpoints ---\n";
http_test("Heartbeat", "GET", "/heartbeat.php", null, "ok");

// 2. Get Services (from cache)
http_test("Get Services", "GET", "/get_service.php");

// 3. Get Recommended
http_test("Get Recommended", "GET", "/get_recommended.php");

// 4. Get Orders
http_test("Get Orders", "GET", "/get_orders.php", null, "orders");

// 5. Get Deposits
http_test("Get Deposits", "GET", "/get_deposits.php");

// 6. Get Alerts
http_test("Get Alerts", "GET", "/get_alerts.php", null, "alerts");

// 7. Mark Alerts Read
http_test("Mark Alerts Read", "GET", "/mark_alerts_read.php", null, "success");

// 8. Check Order Status
http_test("Check Order Status", "GET", "/check_order_status.php", null, "success");

echo "\n--- POST Endpoints ---\n";

// 9. Telegram Auth (will fail validation as expected since we craft test data differently)
$user_json = json_encode(['id' => 777888, 'first_name' => 'TestHTTP', 'last_name' => 'User']);
$auth_date = time();
$params = ['user' => $user_json, 'auth_date' => $auth_date, 'query_id' => 'test_http_123'];
ksort($params);
$data_check_parts = [];
foreach ($params as $key => $value) {
    $data_check_parts[] = "$key=$value";
}
$data_check_string = implode("\n", $data_check_parts);
$BOT_TOKEN = 'YOUR_TELEGRAM_BOT_TOKEN';
$secret_key = hash_hmac('sha256', $BOT_TOKEN, 'WebAppData', true);
$hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));

$params['hash'] = $hash;

// Build initData string
$initData = http_build_query($params);

$result = http_test("Telegram Auth", "POST", "/telegram_auth.php", ['initData' => $initData], "success");
if ($result && isset($result['user'])) {
    echo "    → User: " . json_encode($result['user']) . "\n";
}

// 10. Deposit Handler 
http_test("Deposit Handler (duplicate)", "POST", "/deposit_handler.php", [
    'amount' => 50.00,
    'reference_id' => 'test-ref-001'  // Already exists, should reject
], "status");

// 11. Process Order (without valid service, expect validation error)
$order_result = http_test("Process Order (validation)", "POST", "/process_order.php", [
    'service' => 0,
    'link' => '',
    'quantity' => 0
], "error");

// 12. User Actions (refill with bad order)
http_test("User Actions (bad order)", "POST", "/user_actions.php", [
    'action' => 'refill',
    'order_id' => 0
], "error");

echo "\n" . str_repeat("=", 60) . "\n";
$total = $passed + $failed;
echo "  RESULTS: $passed PASSED / $failed FAILED / $total TOTAL\n";
echo str_repeat("=", 60) . "\n";

if ($failed === 0) {
    echo "\n  ALL HTTP TESTS PASSED!\n\n";
} else {
    echo "\n  $failed test(s) need attention (some failures may be expected validation errors).\n\n";
}

// Cleanup test user
include 'db.php';
mysqli_query($conn, "DELETE FROM auth WHERE tg_id = 777888");
mysqli_query($conn, "DELETE FROM user_alerts WHERE user_id = 777888");
echo "  Cleanup: Removed test user 777888.\n";
?>
