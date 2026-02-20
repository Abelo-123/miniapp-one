<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// update_refunded_orders.php - Update all refunded orders to have 'cancelled' status
include 'db.php';

echo "Updating refunded orders to 'cancelled' status...\n\n";

// Update all orders that have 'canceled' status (API spelling) to 'cancelled' (our spelling)
// Note: We keep both spellings for compatibility
$result = $conn->query("UPDATE orders SET status = 'cancelled' WHERE status = 'canceled'");

if ($result) {
    $affected = $conn->affected_rows;
    echo "✓ Updated $affected orders from 'canceled' to 'cancelled'\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

// Show current status distribution
echo "\nCurrent order status distribution:\n";
$stats = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status ORDER BY count DESC");
while ($row = $stats->fetch_assoc()) {
    echo "  - {$row['status']}: {$row['count']} orders\n";
}

echo "\nDone!\n";
?>
