<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db.php';
$rows = db_query("DESCRIBE auth");
foreach ($rows as $row) {
    echo $row['Field'] . ": " . $row['Type'] . "\n";
}
?>
