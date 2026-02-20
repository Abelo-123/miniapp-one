<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db.php';
echo "Altering 'auth' table to change 'balance' column to DOUBLE(20,8)...\n";
$sql = "ALTER TABLE auth MODIFY COLUMN balance DOUBLE(20,8) DEFAULT 0.00000000";
if (mysqli_query($conn, $sql)) {
    echo "Success!\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
