<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// cron_check_orders.php - Smart cron that only runs when needed
// This completely stops when there are no active orders

// ============================================
// CPANEL DEPLOYMENT - FULL SETUP GUIDE
// ============================================
// 
// STEP 1: Update db.php with your cPanel database credentials
// ---------------------------------------------------------
// $db_host = 'localhost';
// $db_user = 'youruser_paxyodb';  // Your cPanel MySQL username
// $db_pass = 'your_strong_password';
// $db_name = 'youruser_paxyo';
//
// STEP 2: Upload all files to public_html via cPanel File Manager
// ---------------------------------------------------------
// - cron_check_orders.php (this file)
// - order_manager.php
// - realtime_stream.php
// - db.php (with updated credentials)
// - smm.php
// - All other project files
//
// STEP 3: Create cron jobs in cPanel (6 jobs for 10-second intervals)
// ---------------------------------------------------------
// Go to: cPanel → Cron Jobs → Add New Cron Job
//
// Job 1: * * * * * /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
// Job 2: * * * * * sleep 10; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
// Job 3: * * * * * sleep 20; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
// Job 4: * * * * * sleep 30; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
// Job 5: * * * * * sleep 40; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
// Job 6: * * * * * sleep 50; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
//
// IMPORTANT: Replace /home/youruser/public_html/ with YOUR actual path!
// To find your path: cPanel File Manager → Navigate to site root → Copy path from top
//
// STEP 4: Set file permissions (via cPanel File Manager)
// ---------------------------------------------------------
// Right-click each PHP file → Change Permissions → Set to 644
//
// STEP 5: Test the cron (via cPanel Terminal or SSH)
// ---------------------------------------------------------
// php /home/youruser/public_html/cron_check_orders.php
//
// You should see: "Starting order status check..."
//
// THAT'S IT! Your real-time system is now live on cPanel!
// ============================================

require_once 'db.php';
require_once 'order_manager.php';

// Flag file to control cron execution
$temp_dir = __DIR__ . '/temp';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}
$flag_file = $temp_dir . '/paxyo_cron_active.flag';

// Check if there are ANY active orders
$count_check = $conn->query("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE status IN ('pending', 'processing', 'in_progress')
");
$count_row = $count_check->fetch_assoc();
$active_orders_count = $count_row['count'];

if ($active_orders_count == 0) {
    // No active orders - remove flag and exit silently
    if (file_exists($flag_file)) {
        unlink($flag_file);
    }
    // Exit silently - no output when there's nothing to do
    exit(0);
}

// Active orders exist - create/update flag
file_put_contents($flag_file, time());

echo "[" . date('Y-m-d H:i:s') . "] Checking $active_orders_count active orders...\n";

// Get all unique user IDs with active orders
$result = $conn->query("
    SELECT DISTINCT user_id 
    FROM orders 
    WHERE status IN ('pending', 'processing', 'in_progress')
");

$users_checked = 0;
$total_updates = 0;

while ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
    
    // Sync this user's orders
    $sync_result = syncOrderStatuses($user_id, $conn);
    
    if ($sync_result['updated'] > 0) {
        echo "  ✓ User $user_id: {$sync_result['updated']} orders updated\n";
        $total_updates += $sync_result['updated'];
    }
    
    $users_checked++;
}

echo "[" . date('Y-m-d H:i:s') . "] Complete: $total_updates updates\n";
?>
