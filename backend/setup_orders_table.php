<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// setup_orders_table.php
include 'db.php';

echo "Setting up orders table...\n";

// Check if table exists
$tableExists = false;
$result = $conn->query("SHOW TABLES LIKE 'orders'");
if ($result->num_rows > 0) {
    $tableExists = true;
    echo "Table 'orders' already exists. Checking columns...\n";
} else {
    echo "Creating 'orders' table...\n";
    $sql = "CREATE TABLE `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` bigint(20) NOT NULL,
        `api_order_id` bigint(20) NOT NULL,
        `service_id` int(11) NOT NULL,
        `service_name` varchar(255) DEFAULT NULL,
        `link` text DEFAULT NULL,
        `quantity` int(11) NOT NULL,
        `charge` double(20,8) NOT NULL,
        `status` varchar(50) DEFAULT 'pending',
        `start_count` int(11) DEFAULT 0,
        `remains` int(11) DEFAULT 0,
        `currency` varchar(10) DEFAULT 'USD',
        `created_at` datetime DEFAULT current_timestamp(),
        `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `api_order_id` (`api_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'orders' created successfully.\n";
    } else {
        die("Error creating table: " . $conn->error . "\n");
    }
}

// If table exists, ensure all columns are there
if ($tableExists) {
    $columns = [];
    $res = $conn->query("SHOW COLUMNS FROM orders");
    while($row = $res->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $required = [
        'api_order_id' => "ADD COLUMN `api_order_id` bigint(20) NOT NULL AFTER `user_id`",
        'service_name' => "ADD COLUMN `service_name` varchar(255) DEFAULT NULL AFTER `service_id`",
        'status' => "ADD COLUMN `status` varchar(50) DEFAULT 'pending'",
        'start_count' => "ADD COLUMN `start_count` int(11) DEFAULT 0",
        'remains' => "ADD COLUMN `remains` int(11) DEFAULT 0",
        'updated_at' => "ADD COLUMN `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()"
    ];

    foreach ($required as $col => $sql) {
        if (!in_array($col, $columns)) {
            echo "Adding missing column: $col\n";
            if (!$conn->query("ALTER TABLE orders $sql")) {
                echo "Error adding column $col: " . $conn->error . "\n";
            }
        }
    }
    
    // Check indexes
    $indexes = [];
    $res = $conn->query("SHOW INDEX FROM orders");
    while($row = $res->fetch_assoc()) {
        $indexes[] = $row['Key_name'];
    }
    
    if (!in_array('user_id', $indexes)) {
        $conn->query("ALTER TABLE orders ADD INDEX `user_id` (`user_id`)");
        echo "Added index for user_id\n";
    }
}

echo "Done.\n";
?>
