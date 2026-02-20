<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db.php';
$res = $conn->query('SELECT id, charge, status, quantity FROM orders WHERE id=19');
while($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
