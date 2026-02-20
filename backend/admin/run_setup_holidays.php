<?php
include '../db.php';
$sql = file_get_contents('setup_holidays.sql');
if (mysqli_multi_query($conn, $sql)) {
    echo "SQL setup executed successfully.";
} else {
    echo "Error executing SQL: " . mysqli_error($conn);
}
?>
