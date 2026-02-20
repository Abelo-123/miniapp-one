<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * FIX_DATABASE.PHP
 * Run this ONCE to fix database column issues
 * 
 * This will:
 * 1. Check if created_at column exists in orders table
 * 2. Add it if missing
 * 3. Populate it with current timestamps
 */

include 'db.php';

echo "<h2>🔧 Database Fix Utility</h2>";

// Fix orders table
echo "<h3>Checking orders table...</h3>";

$check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'created_at'");
if (mysqli_num_rows($check) == 0) {
    echo "<p>⚠️ created_at column missing in orders table. Adding it...</p>";
    
    // Add the column
    $add = mysqli_query($conn, "ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    
    if ($add) {
        echo "<p style='color:green'>✅ created_at column added successfully!</p>";
        
        // Update existing rows
        $update = mysqli_query($conn, "UPDATE orders SET created_at = NOW() WHERE created_at IS NULL");
        if ($update) {
            echo "<p style='color:green'>✅ Existing rows updated!</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ created_at column already exists!</p>";
}

// Fix auth table
echo "<h3>Checking auth table...</h3>";

$check = mysqli_query($conn, "SHOW COLUMNS FROM auth LIKE 'created_at'");
if (mysqli_num_rows($check) == 0) {
    echo "<p>⚠️ created_at column missing in auth table. Adding it...</p>";
    
    $add = mysqli_query($conn, "ALTER TABLE auth ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    
    if ($add) {
        echo "<p style='color:green'>✅ created_at column added successfully!</p>";
        
        $update = mysqli_query($conn, "UPDATE auth SET created_at = NOW() WHERE created_at IS NULL");
        if ($update) {
            echo "<p style='color:green'>✅ Existing rows updated!</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ created_at column already exists!</p>";
}

// Create deposits table if it doesn't exist
echo "<h3>Checking deposits table...</h3>";

$check = mysqli_query($conn, "SHOW TABLES LIKE 'deposits'");
if (mysqli_num_rows($check) == 0) {
    echo "<p>⚠️ deposits table missing. Creating it...</p>";
    
    $create = mysqli_query($conn, "
        CREATE TABLE deposits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            payment_method VARCHAR(50),
            transaction_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(status),
            INDEX(created_at)
        )
    ");
    
    if ($create) {
        echo "<p style='color:green'>✅ deposits table created!</p>";
    } else {
        echo "<p style='color:red'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ deposits table already exists!</p>";
    
    // Check if it has created_at
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM deposits LIKE 'created_at'");
    if (mysqli_num_rows($check_col) == 0) {
        echo "<p>⚠️ Adding created_at to deposits table...</p>";
        $add = mysqli_query($conn, "ALTER TABLE deposits ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        if ($add) {
            echo "<p style='color:green'>✅ created_at added to deposits!</p>";
        }
    }
}

// Create holidays table if it doesn't exist
echo "<h3>Checking holidays table...</h3>";

$check = mysqli_query($conn, "SHOW TABLES LIKE 'holidays'");
if (mysqli_num_rows($check) == 0) {
    echo "<p>⚠️ holidays table missing. Creating it...</p>";
    
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
    
    if ($create) {
        echo "<p style='color:green'>✅ holidays table created!</p>";
    } else {
        echo "<p style='color:red'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ holidays table already exists!</p>";
}

echo "<hr>";
echo "<h2>✅ Database Fix Complete!</h2>";
echo "<p><strong>Now try refreshing your admin panel.</strong></p>";
echo "<p><a href='admin/index.php' style='padding:10px 20px; background:#6c5ce7; color:white; text-decoration:none; border-radius:4px;'>Go to Admin Panel</a></p>";
?>
