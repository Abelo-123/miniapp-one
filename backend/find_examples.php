<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
$json = file_get_contents('cache/services.json');
$data = json_decode($json, true);

$types_found = [];
$examples = [];

foreach ($data as $s) {
    $type = $s['type'];
    if (!isset($types_found[$type])) {
        $types_found[$type] = true;
        // Clean name for display
        $name = substr($s['name'], 0, 50) . '...';
        $examples[] = "Type: [{$type}]\nID: {$s['service']}\nName: {$name}\nCategory: {$s['category']}\n";
    }
}

echo implode("\n-------------------\n", $examples);
?>
