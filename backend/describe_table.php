<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db.php';
$res = mysqli_query($conn, 'DESCRIBE service_adjustments');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
