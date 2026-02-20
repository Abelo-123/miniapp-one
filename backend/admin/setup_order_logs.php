<?php
// admin/setup_order_logs.php - Create order_logs table
require_once '../db.php';

// Get connection
global $conn;
if (!isset($conn)) {
    // Fallback connection
    $conn = mysqli_connect('localhost', 'root', '', 'paxyo');
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}

echo "Creating order_logs table...\n\n";

$sql = "CREATE TABLE IF NOT EXISTS order_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "✓ Table 'order_logs' created successfully\n";
} else {
    echo "✗ Error creating table: " . mysqli_error($conn) . "\n";
}

echo "\nDone!\n";
?>
