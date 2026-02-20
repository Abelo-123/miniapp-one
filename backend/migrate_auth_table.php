<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Add Telegram user profile columns to auth table
 * Run this once to add username, first_name, last_name, photo_url, and timestamps
 */

require_once 'db.php';

echo "Adding Telegram profile columns to auth table...\n";

$migrations = [
    // Add username column
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS username VARCHAR(255) NULL AFTER tg_id",
    
    // Add first_name column
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS first_name VARCHAR(255) NULL AFTER username",
    
    // Add last_name column
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS last_name VARCHAR(255) NULL AFTER first_name",
    
    // Add photo_url column
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS photo_url TEXT NULL AFTER last_name",
    
    // Add created_at timestamp
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER balance",
    
    // Add last_login timestamp
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    
    // Add last_seen timestamp for real-time online status
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL AFTER last_login",
    
    // Add index on username for faster lookups
    "ALTER TABLE auth ADD INDEX IF NOT EXISTS idx_username (username)"
];

$success = 0;
$failed = 0;

foreach ($migrations as $sql) {
    // MySQL doesn't support IF NOT EXISTS for all ALTER TABLE commands
    // So we'll check and handle errors gracefully
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo "✅ Success: " . substr($sql, 0, 60) . "...\n";
        $success++;
    } else {
        $error = mysqli_error($conn);
        // Ignore "Duplicate column" errors as that means the column already exists
        if (strpos($error, 'Duplicate column') !== false || strpos($error, 'Duplicate key') !== false) {
            echo "⏭️ Skipped (already exists): " . substr($sql, 0, 60) . "...\n";
        } else {
            echo "❌ Failed: " . $error . "\n";
            $failed++;
        }
    }
}

// Alternative approach for MySQL versions that don't support IF NOT EXISTS
$columns_to_add = [
    'username' => "ALTER TABLE auth ADD COLUMN username VARCHAR(255) NULL",
    'first_name' => "ALTER TABLE auth ADD COLUMN first_name VARCHAR(255) NULL",
    'last_name' => "ALTER TABLE auth ADD COLUMN last_name VARCHAR(255) NULL",
    'photo_url' => "ALTER TABLE auth ADD COLUMN photo_url TEXT NULL",
    'created_at' => "ALTER TABLE auth ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    'last_login' => "ALTER TABLE auth ADD COLUMN last_login TIMESTAMP NULL",
    'last_seen' => "ALTER TABLE auth ADD COLUMN last_seen TIMESTAMP NULL"
];

// Check which columns exist
$existing_columns = [];
$result = mysqli_query($conn, "DESCRIBE auth");
while ($row = mysqli_fetch_assoc($result)) {
    $existing_columns[] = $row['Field'];
}

foreach ($columns_to_add as $col => $sql) {
    if (!in_array($col, $existing_columns)) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            echo "✅ Added column: $col\n";
        } else {
            echo "❌ Failed to add $col: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\n✅ Migration complete!\n";
echo "New users will now be registered with their Telegram profile data.\n";
?>
