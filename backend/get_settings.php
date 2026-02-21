<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header('Content-Type: application/json');
require_once 'config/database.php';

$settings = [
    'rateMultiplier' => 1.0,
    'discountPercent' => 0,
    'holidayName' => '',
    'maintenanceMode' => false,
    'userCanOrder' => true,
    'marqueeText' => 'Welcome to PaxYo!'
];

$stmt = $pdo->query("SELECT * FROM settings");
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $key = $row['setting_key'];
    if (isset($settings[$key])) {
        $val = $row['setting_value'];
        if ($key === 'maintenanceMode' || $key === 'userCanOrder') {
            $val = (bool)$val;
        }
        $settings[$key] = $val;
    }
}

echo json_encode($settings);
