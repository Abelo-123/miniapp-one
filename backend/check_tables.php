<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// check_tables.php - Quick diagnostic to check table structure
include 'db.php';

echo "<h2>Checking Database Tables...</h2>";

// Check orders table
echo "<h3>Orders Table Columns:</h3>";
$result = mysqli_query($conn, "DESCRIBE orders");
if ($result) {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    echo "<p>Orders table might not exist yet.</p>";
}

// Check auth table
echo "<h3>Auth Table Columns:</h3>";
$result = mysqli_query($conn, "DESCRIBE auth");
if ($result) {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
}

// Check deposits table
echo "<h3>Deposits Table Columns:</h3>";
$result = mysqli_query($conn, "DESCRIBE deposits");
if ($result) {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    echo "<p>Deposits table might not exist yet.</p>";
}

echo "<hr>";
echo "<h3>Sample Data from Orders (if exists):</h3>";
$result = mysqli_query($conn, "SELECT * FROM orders LIMIT 5");
if ($result) {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($result)) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "<p>No orders data or table doesn't exist</p>";
}
?>
