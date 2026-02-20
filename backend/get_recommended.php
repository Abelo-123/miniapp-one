<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// get_recommended.php
header('Content-Type: application/json');
include 'db.php';

$result = mysqli_query($conn, "SELECT service_id FROM admin_recommended_services");
$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = intval($row['service_id']);
}

echo json_encode($services);
?>
