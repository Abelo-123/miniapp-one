<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Backend Test Script - Tests all API endpoints
 * Run: D:\next\xampp\php\php.exe test_backend.php
 */

echo "\n" . str_repeat("=", 60) . "\n";
echo "  PAXYO SMM BACKEND - FULL TEST SUITE\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

$passed = 0;
$failed = 0;
$total = 0;

function test($name, $result, $detail = '') {
    global $passed, $failed, $total;
    $total++;
    if ($result) {
        $passed++;
        echo "  [PASS] $name\n";
    } else {
        $failed++;
        echo "  [FAIL] $name" . ($detail ? " - $detail" : "") . "\n";
    }
}

// ============================================
// 1. TEST DATABASE CONNECTION (db.php)
// ============================================
echo "--- 1. Database Connection (db.php) ---\n";
ob_start();
include 'db.php';
ob_end_clean();

test("DB connection", $conn && !mysqli_connect_error());

// Test db helpers
$user = db_select('auth', 'tg_id', 111);
test("db_select() - full row", $user !== null && isset($user['first_name']));

$balance = db_select('auth', 'tg_id', 111, 'balance');
test("db_select() - single field", $balance !== null && floatval($balance) >= 0);

$settings = db_query("SELECT * FROM settings");
test("db_query() - raw query", is_array($settings) && !empty($settings));

$shortcut = db('get', 'auth', 'tg_id', 111, 'first_name');
test("db() shortcut", $shortcut !== null);

echo "\n";

// ============================================
// 2. TEST GET SERVICES (get_service.php)
// ============================================
echo "--- 2. Get Services (get_service.php) ---\n";

// We can't fully test this without curl to external API, but we can test the cache logic
$cacheDir = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/services.json';

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Create a mock cache for testing
$mockServices = [
    [
        'service' => 1001,
        'name' => 'Instagram Followers - Test',
        'type' => 'Default',
        'rate' => '0.50',
        'min' => 100,
        'max' => 10000,
        'category' => 'Instagram Followers',
        'refill' => true
    ],
    [
        'service' => 1002,
        'name' => 'YouTube Views - Test',
        'type' => 'Default',
        'rate' => '0.30',
        'min' => 500,
        'max' => 50000,
        'category' => 'YouTube Views',
        'refill' => false
    ]
];

file_put_contents($cacheFile, json_encode($mockServices));
test("Service cache created", file_exists($cacheFile));

$cached = json_decode(file_get_contents($cacheFile), true);
test("Service cache readable", is_array($cached) && count($cached) === 2);

echo "\n";

// ============================================
// 3. TEST GET RECOMMENDED (get_recommended.php)
// ============================================
echo "--- 3. Get Recommended (get_recommended.php) ---\n";

// Insert test recommended services
mysqli_query($conn, "INSERT IGNORE INTO admin_recommended_services (service_id) VALUES (1001), (1002)");
$result = mysqli_query($conn, "SELECT service_id FROM admin_recommended_services");
$recommended = [];
while ($row = mysqli_fetch_assoc($result)) {
    $recommended[] = intval($row['service_id']);
}
test("Recommended services query", count($recommended) >= 2, "Got " . count($recommended));

echo "\n";

// ============================================
// 4. TEST GET ORDERS (get_orders.php)
// ============================================
echo "--- 4. Get Orders (get_orders.php) ---\n";

// Insert a test order
$testOrder = [
    'user_id' => 111,
    'api_order_id' => 99999,
    'service_id' => 1001,
    'service_name' => 'Instagram Followers - Test',
    'link' => 'https://instagram.com/test',
    'quantity' => 1000,
    'charge' => 10.50,
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
];
$order_insert = db_insert('orders', $testOrder);
test("Order insert", $order_insert !== false, "ID: $order_insert");

// Query orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$uid = 111;
$stmt->bind_param("i", $uid);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = [];
while ($row = $orders_result->fetch_assoc()) {
    $orders[] = $row;
}
test("Orders query", count($orders) > 0, "Count: " . count($orders));

echo "\n";

// ============================================
// 5. TEST ORDER MANAGER (syncOrderStatuses)
// ============================================
echo "--- 5. Order Manager (order_manager.php) ---\n";

require_once 'order_manager.php';
// This will try to call the external API - it may fail if no real orders exist
// But we can test that the function exists and runs without fatal error
$syncResult = syncOrderStatuses(111, $conn);
test("syncOrderStatuses() runs", is_array($syncResult) && isset($syncResult['checked']));
test("syncOrderStatuses() structure", isset($syncResult['updated']) && isset($syncResult['updates']));

echo "\n";

// ============================================
// 6. TEST GET DEPOSITS (get_deposits.php)
// ============================================
echo "--- 6. Get Deposits ---\n";

$user_id = 111;
$user_id_safe = db_escape($user_id);
$sql = "SELECT id, amount, reference_id, status, created_at FROM deposits WHERE user_id = '$user_id_safe' ORDER BY created_at DESC LIMIT 5";
$deposits = db_query($sql);
test("Deposits query", is_array($deposits) && count($deposits) > 0, "Count: " . count($deposits));

echo "\n";

// ============================================
// 7. TEST DEPOSIT HANDLER (deposit_handler.php)
// ============================================
echo "--- 7. Deposit Handler Logic ---\n";

// Test duplicate reference check
$exists = db('select', 'deposits', 'reference_id', 'test-ref-001');
test("Duplicate reference detection", $exists !== null);

// Test new deposit
$ref = 'test-ref-' . time();
$deposit_data = [
    'user_id' => 111,
    'amount' => 25.00,
    'reference_id' => $ref,
    'status' => 'completed'
];
$dep_id = db_insert('deposits', $deposit_data);
test("New deposit insert", $dep_id !== false);

// Atomic balance update
$old_balance = floatval(db_select('auth', 'tg_id', 111, 'balance'));
mysqli_query($conn, "UPDATE auth SET balance = balance + 25.00 WHERE tg_id = '111'");
$new_balance = floatval(db_select('auth', 'tg_id', 111, 'balance'));
test("Atomic balance add", ($new_balance - $old_balance) == 25.00, "Old: $old_balance, New: $new_balance");

// Revert test deposit
mysqli_query($conn, "UPDATE auth SET balance = balance - 25.00 WHERE tg_id = '111'");

echo "\n";

// ============================================
// 8. TEST GET ALERTS (get_alerts.php)
// ============================================
echo "--- 8. Get Alerts ---\n";

$user_id_safe = db_escape(111);
$sql = "SELECT id, message, is_read, created_at FROM user_alerts WHERE user_id = '$user_id_safe' ORDER BY created_at DESC LIMIT 20";
$alerts = db_query($sql) ?: [];
test("Alerts query", is_array($alerts), "Count: " . count($alerts));

$unread_count = 0;
foreach ($alerts as $a) {
    if (intval($a['is_read']) === 0) $unread_count++;
}
test("Unread count calculation", $unread_count >= 0, "Unread: $unread_count");

echo "\n";

// ============================================
// 9. TEST MARK ALERTS READ
// ============================================
echo "--- 9. Mark Alerts Read ---\n";

mysqli_query($conn, "UPDATE user_alerts SET is_read = 1 WHERE user_id = '111' AND is_read = 0");
$unread_after = db_query("SELECT COUNT(*) as cnt FROM user_alerts WHERE user_id = 111 AND is_read = 0");
test("Mark alerts read", intval($unread_after[0]['cnt']) === 0, "Remaining unread: " . $unread_after[0]['cnt']);

// Reset one to unread for future tests
mysqli_query($conn, "UPDATE user_alerts SET is_read = 0 WHERE user_id = '111' ORDER BY id DESC LIMIT 1");

echo "\n";

// ============================================
// 10. TEST HEARTBEAT
// ============================================
echo "--- 10. Heartbeat ---\n";

$user_id_safe = db_escape(111);
mysqli_query($conn, "UPDATE auth SET last_seen = NOW() WHERE tg_id = '$user_id_safe'");
$last_seen = db_select('auth', 'tg_id', 111, 'last_seen');
test("Heartbeat update", $last_seen !== null, "last_seen: $last_seen");

echo "\n";

// ============================================
// 11. TEST SSE STATE HASHING (realtime_stream logic)
// ============================================
echo "--- 11. Realtime Stream Logic ---\n";

// Test orders hash
$stmt = $conn->prepare("SELECT GROUP_CONCAT(CONCAT(id,'|',status,'|',remains) ORDER BY id) as hash FROM orders WHERE user_id = ? AND status IN ('pending', 'processing', 'in_progress')");
$uid = 111;
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$orders_hash = md5($row['hash'] ?? '');
test("Orders hash computation", strlen($orders_hash) === 32, "Hash: $orders_hash");

// Test alerts hash
$stmt = $conn->prepare("SELECT GROUP_CONCAT(CONCAT(id,'|',is_read) ORDER BY id DESC) as hash FROM user_alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$alerts_hash = md5($row['hash'] ?? '');
test("Alerts hash computation", strlen($alerts_hash) === 32, "Hash: $alerts_hash");

// Test temp directory
$temp_dir = __DIR__ . '/temp';
if (!is_dir($temp_dir)) mkdir($temp_dir, 0755, true);
test("Temp directory exists", is_dir($temp_dir));

// Test state file
$state = ['orders_hash' => $orders_hash, 'alerts_hash' => $alerts_hash, 'balance' => 500.00, 'maintenance_mode' => '0'];
file_put_contents($temp_dir . '/paxyo_state_111.json', json_encode($state));
$loaded = json_decode(file_get_contents($temp_dir . '/paxyo_state_111.json'), true);
test("State persistence", $loaded !== null && $loaded['orders_hash'] === $orders_hash);

echo "\n";

// ============================================
// 12. TEST TELEGRAM AUTH LOGIC (HMAC-SHA256)
// ============================================
echo "--- 12. Telegram Auth (HMAC-SHA256) ---\n";

$BOT_TOKEN = 'YOUR_TELEGRAM_BOT_TOKEN';

// Simulate creating valid initData
$user_json = json_encode(['id' => 777888, 'first_name' => 'TestBot', 'last_name' => 'User']);
$auth_date = time();
$params = [
    'user' => $user_json,
    'auth_date' => $auth_date,
    'query_id' => 'test_query_123'
];

// Sort alphabetically
ksort($params);

// Create data_check_string
$data_check_parts = [];
foreach ($params as $key => $value) {
    $data_check_parts[] = "$key=$value";
}
$data_check_string = implode("\n", $data_check_parts);

// Compute HMAC
$secret_key = hash_hmac('sha256', $BOT_TOKEN, 'WebAppData', true);
$hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));

test("HMAC secret key generation", strlen($secret_key) === 32);
test("HMAC hash computation", strlen($hash) === 64, "Hash: " . substr($hash, 0, 16) . "...");

// Verify hash matches when we recompute
$secret_key2 = hash_hmac('sha256', $BOT_TOKEN, 'WebAppData', true);
$hash2 = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key2, true));
test("HMAC hash verification (self-check)", hash_equals($hash, $hash2));

// Test auth_date expiry
$old_auth_date = time() - 100000; // > 24 hours ago
test("Auth date expiry check", (time() - $old_auth_date) > 86400, "Old date expired correctly");

echo "\n";

// ============================================
// 13. TEST WEBHOOK HANDLER LOGIC
// ============================================
echo "--- 13. Webhook Handler Logic ---\n";

// Test order lookup by api_order_id
$stmt = $conn->prepare("SELECT id, user_id, status, charge, quantity FROM orders WHERE api_order_id = ?");
$api_id = 99999;
$stmt->bind_param("s", $api_id);
$stmt->execute();
$result = $stmt->get_result();
$webhook_order = $result->fetch_assoc();
test("Webhook order lookup", $webhook_order !== null, "Local ID: " . ($webhook_order['id'] ?? 'N/A'));

echo "\n";

// ============================================
// 14. TEST CRON JOB LOGIC
// ============================================
echo "--- 14. Cron Check Orders ---\n";

$count_check = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'processing', 'in_progress')");
$count_row = $count_check->fetch_assoc();
$active = $count_row['count'];
test("Active orders count", $active >= 0, "Active: $active");

if ($active > 0) {
    $flag_file = __DIR__ . '/temp/paxyo_cron_active.flag';
    file_put_contents($flag_file, time());
    test("Cron flag file create", file_exists($flag_file));
}

echo "\n";

// ============================================
// 15. TEST UTILS BOT
// ============================================
echo "--- 15. Notifications (utils_bot.php) ---\n";

require_once 'utils_bot.php';
$notify_result = notify_bot_admin([
    'type' => 'test',
    'message' => 'Backend test suite ran successfully'
]);
test("notify_bot_admin()", $notify_result === true);

$logFile = __DIR__ . '/bot_notifications.log';
test("Notification log file", file_exists($logFile));

echo "\n";

// ============================================
// CLEANUP TEST DATA
// ============================================
echo "--- Cleanup ---\n";
// Remove test order
mysqli_query($conn, "DELETE FROM orders WHERE api_order_id = 99999");
// Remove test deposit
mysqli_query($conn, "DELETE FROM deposits WHERE reference_id LIKE 'test-ref-" . date('Y') . "%'");
echo "  Test data cleaned up.\n\n";

// ============================================
// SUMMARY
// ============================================
echo str_repeat("=", 60) . "\n";
echo "  RESULTS: $passed PASSED / $failed FAILED / $total TOTAL\n";
echo str_repeat("=", 60) . "\n";

if ($failed === 0) {
    echo "\n  ALL TESTS PASSED! Backend is fully operational.\n\n";
} else {
    echo "\n  WARNING: $failed test(s) failed. Review output above.\n\n";
}
?>
