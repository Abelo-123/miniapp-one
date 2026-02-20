<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Migration: Add Google OAuth columns to auth table
 * Run this once to add support for Google authentication
 */

require_once 'db.php';

echo "<pre style='font-family: monospace; background: #1a1a24; color: #00d26a; padding: 20px; border-radius: 8px;'>\n";
echo "=== Google OAuth Migration ===\n\n";

$migrations = [
    // Add google_id column
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL",
    
    // Add email column
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL",
    
    // Add auth_provider column (telegram, google, or both)
    "ALTER TABLE auth ADD COLUMN IF NOT EXISTS auth_provider VARCHAR(50) DEFAULT 'telegram'",
    
    // Add indexes for faster lookups
    "ALTER TABLE auth ADD INDEX IF NOT EXISTS idx_google_id (google_id)",
    "ALTER TABLE auth ADD INDEX IF NOT EXISTS idx_email (email)",
    "ALTER TABLE auth ADD INDEX IF NOT EXISTS idx_auth_provider (auth_provider)"
];

$success = 0;
$failed = 0;

foreach ($migrations as $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo "✓ " . substr($sql, 0, 60) . "...\n";
        $success++;
    } else {
        $error = mysqli_error($conn);
        // Ignore duplicate column/key errors
        if (strpos($error, 'Duplicate') !== false || strpos($error, 'already exists') !== false) {
            echo "○ Already exists: " . substr($sql, 0, 50) . "...\n";
            $success++;
        } else {
            echo "✗ Failed: " . $error . "\n";
            $failed++;
        }
    }
}

echo "\n=== Migration Complete ===\n";
echo "Success: $success\n";
echo "Failed: $failed\n";

// Show current table structure
echo "\n=== Current auth Table Structure ===\n";
$result = mysqli_query($conn, "DESCRIBE auth");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $nullable = $row['Null'] === 'YES' ? ' (nullable)' : '';
        echo $row['Field'] . " - " . $row['Type'] . $nullable . "\n";
    }
}

echo "</pre>";
?>
