<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db.php';
$res = $conn->query('SHOW TABLES LIKE "user_alerts"');
echo $res->num_rows > 0 ? "✅ Table exists\n" : "❌ Table NOT found - Creating it now...\n";

if ($res->num_rows == 0) {
    // Create the table
    $sql = "CREATE TABLE IF NOT EXISTS user_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_read (user_id, is_read),
        INDEX idx_created (created_at)
    )";
    
    if ($conn->query($sql)) {
        echo "✅ user_alerts table created!\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}
?>
