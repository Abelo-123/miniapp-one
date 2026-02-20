<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// fix_database_cli.php - CLI version of database fix
// Run this via terminal to fix database structure

// Suppress HTML output from db.php if any (there is none, but good practice)
ob_start();
include 'db.php';
ob_end_clean();

echo "Starting Database Fix...\n";
echo "-----------------------\n";

if (!$conn) {
    die("Error: Could not connect to database.\n");
}

// 1. Fix orders table
echo "[1/4] Checking 'orders' table...\n";
$check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'created_at'");
if (mysqli_num_rows($check) == 0) {
    echo "  -> 'created_at' missing. Adding...\n";
    $add = mysqli_query($conn, "ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    if ($add) {
        mysqli_query($conn, "UPDATE orders SET created_at = NOW() WHERE created_at IS NULL");
        echo "  -> SUCCESS: Added 'created_at' to orders.\n";
    } else {
        echo "  -> FAILED: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "  -> 'created_at' already exists.\n";
}

// 2. Fix auth table
echo "[2/4] Checking 'auth' table...\n";
$check = mysqli_query($conn, "SHOW COLUMNS FROM auth LIKE 'created_at'");
if (mysqli_num_rows($check) == 0) {
    echo "  -> 'created_at' missing. Adding...\n";
    $add = mysqli_query($conn, "ALTER TABLE auth ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    if ($add) {
        mysqli_query($conn, "UPDATE auth SET created_at = NOW() WHERE created_at IS NULL");
        echo "  -> SUCCESS: Added 'created_at' to auth.\n";
    } else {
        echo "  -> FAILED: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "  -> 'created_at' already exists.\n";
}

// 3. Fix deposits table
echo "[3/4] Checking 'deposits' table...\n";
$check = mysqli_query($conn, "SHOW TABLES LIKE 'deposits'");
if (mysqli_num_rows($check) == 0) {
    echo "  -> 'deposits' table missing. Creating...\n";
    $create = mysqli_query($conn, "
        CREATE TABLE deposits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            payment_method VARCHAR(50),
            transaction_id VARCHAR(100),
            reference_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(status),
            INDEX(created_at)
        )
    ");
    if ($create) echo "  -> SUCCESS: Created 'deposits' table.\n";
    else echo "  -> FAILED: " . mysqli_error($conn) . "\n";
} else {
    echo "  -> 'deposits' table exists.\n";
    // Check for reference_id and created_at
    $cols = mysqli_query($conn, "SHOW COLUMNS FROM deposits");
    $has_ref = false; $has_created = false;
    while($c = mysqli_fetch_assoc($cols)) {
        if ($c['Field'] == 'reference_id') $has_ref = true;
        if ($c['Field'] == 'created_at') $has_created = true;
    }
    
    if (!$has_created) {
        mysqli_query($conn, "ALTER TABLE deposits ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "  -> Added 'created_at' to deposits.\n";
    }
    if (!$has_ref) {
        mysqli_query($conn, "ALTER TABLE deposits ADD COLUMN reference_id VARCHAR(100)");
        echo "  -> Added 'reference_id' to deposits.\n";
    }
}

// 4. Fix holidays table
echo "[4/4] Checking 'holidays' table...\n";
$check = mysqli_query($conn, "SHOW TABLES LIKE 'holidays'");
if (mysqli_num_rows($check) == 0) {
    echo "  -> 'holidays' table missing. Creating...\n";
    $create = mysqli_query($conn, "
        CREATE TABLE holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            discount_percent DECIMAL(5,2) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(status),
            INDEX(start_date),
            INDEX(end_date)
        )
    ");
    if ($create) echo "  -> SUCCESS: Created 'holidays' table.\n";
    else echo "  -> FAILED: " . mysqli_error($conn) . "\n";
} else {
    echo "  -> 'holidays' table exists.\n";
}

echo "-----------------------\n";
echo "Done! Database structure is now correct.\n";
?>
