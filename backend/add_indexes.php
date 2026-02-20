<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Database Optimization - Add Indexes for Performance
 * Run this once to add indexes to frequently queried columns
 */

include 'db.php';

// Disable mysqli exceptions to handle errors gracefully
mysqli_report(MYSQLI_REPORT_OFF);

echo "<h2>Adding Database Indexes...</h2><pre>";

$indexes = [
    // Orders table indexes
    "ALTER TABLE orders ADD INDEX idx_user_id (user_id)" => "orders.user_id",
    "ALTER TABLE orders ADD INDEX idx_status (status)" => "orders.status",
    "ALTER TABLE orders ADD INDEX idx_user_status (user_id, status)" => "orders.user_id + status (composite)",
    "ALTER TABLE orders ADD INDEX idx_created_at (created_at)" => "orders.created_at",
    
    // Deposits table indexes
    "ALTER TABLE deposits ADD INDEX idx_user_id (user_id)" => "deposits.user_id",
    "ALTER TABLE deposits ADD INDEX idx_status (status)" => "deposits.status",
    "ALTER TABLE deposits ADD INDEX idx_created_at (created_at)" => "deposits.created_at",
    
    // User alerts table indexes
    "ALTER TABLE user_alerts ADD INDEX idx_user_id (user_id)" => "user_alerts.user_id",
    "ALTER TABLE user_alerts ADD INDEX idx_user_read (user_id, is_read)" => "user_alerts.user_id + is_read",
    
    // Auth table indexes (tg_id should already be primary key)
    "ALTER TABLE auth ADD INDEX idx_last_seen (last_seen)" => "auth.last_seen",
];

$success = 0;
$skipped = 0;

foreach ($indexes as $sql => $description) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo "✅ Added index: $description\n";
        $success++;
    } else {
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate key name') !== false || strpos($error, 'Duplicate entry') !== false) {
            echo "⏭️ Index already exists: $description\n";
            $skipped++;
        } else {
            echo "❌ Failed: $description - $error\n";
        }
    }
}

echo "\n<b>Summary:</b> $success added, $skipped already existed\n";
echo "</pre>";

echo "<p>✅ <b>Database optimization complete!</b> You can delete this file now.</p>";
?>

