<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
$json = file_get_contents('cache/services.json');
$data = json_decode($json, true);
$types = array_unique(array_column($data, 'type'));
echo implode("\n", $types);
?>
