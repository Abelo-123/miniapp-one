<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
$cacheFile = __DIR__ . '/cache/services.json';
if (!file_exists($cacheFile)) {
    echo "Cache file not found.\n";
    exit;
}

$data = json_decode(file_get_contents($cacheFile), true);
if (!$data) {
    echo "Cache file empty or invalid JSON.\n";
    exit;
}

$found = false;
foreach ($data as $s) {
    if ($s['service'how e] == 7343) {
        echo "Found Service 7343:\n";
        print_r($s);
        $found = true;
        break;
    }
}

if (!$found) {
    echo "Service 7343 NOT found in cache. Total services: " . count($data) . "\n";
}
?>
