<?php
// get_settings.php - Mini-app settings API
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include 'db.php';

$settings = db_query('SELECT setting_key, setting_value FROM settings');

$output = [
    'rateMultiplier' => 1,
    'discountPercent' => 0,
    'holidayName' => '',
    'maintenanceMode' => false,
    'marqueeText' => 'Welcome to Paxyo SMM!',
    'userCanOrder' => true,
];

if ($settings) {
    foreach ($settings as $s) {
        $key = $s['setting_key'];
        $value = $s['setting_value'];
        
        if ($key === 'rate_multiplier') {
            $output['rateMultiplier'] = floatval($value) / 100;
        } elseif ($key === 'maintenance_mode') {
            $output['maintenanceMode'] = (bool)$value;
            $output['userCanOrder'] = !$value;
        } elseif ($key === 'marquee_text') {
            $output['marqueeText'] = $value;
        }
    }
}

echo json_encode($output);
