<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// fix_charge_precision.php - Fix the charge column to support more decimal places
include 'db.php';

echo "Fixing charge column precision...\n";

// Change charge column from DECIMAL(10,4) to DECIMAL(20,8)
// This supports amounts as small as $0.00000001
$sql = "ALTER TABLE orders MODIFY COLUMN charge DECIMAL(20,8) NOT NULL DEFAULT 0";
if ($conn->query($sql)) {
    echo "✓ Charge column updated to DECIMAL(20,8)\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

// Also update balance column in auth table for consistency
$sql2 = "ALTER TABLE auth MODIFY COLUMN balance DOUBLE(20,8) NOT NULL DEFAULT 0";
if ($conn->query($sql2)) {
    echo "✓ Balance column updated to DOUBLE(20,8)\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

echo "\nDone! Now your refunds will work correctly.\n";
echo "Please place a new test order to verify.\n";
?>
