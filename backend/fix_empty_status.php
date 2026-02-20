<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// fix_empty_status.php - Update orders with empty status
include 'db.php';

echo "Fixing orders with empty status...\n\n";

// First, let's see what we have
$check = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
echo "Current status distribution:\n";
while ($row = $check->fetch_assoc()) {
    $status = $row['status'] === '' ? '(empty)' : $row['status'];
    echo "  - $status: {$row['count']} orders\n";
}

echo "\n";

// Update empty status orders to 'cancelled' 
// (assuming empty status means they were refunded/cancelled)
$result = $conn->query("UPDATE orders SET status = 'cancelled' WHERE status = '' OR status IS NULL");

if ($result) {
    $affected = $conn->affected_rows;
    echo "✓ Updated $affected orders from empty status to 'cancelled'\n\n";
} else {
    echo "✗ Error: " . $conn->error . "\n\n";
}

// Show updated distribution
$check2 = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
echo "Updated status distribution:\n";
while ($row = $check2->fetch_assoc()) {
    $status = $row['status'] === '' ? '(empty)' : $row['status'];
    echo "  - $status: {$row['count']} orders\n";
}

echo "\nDone!\n";
?>
