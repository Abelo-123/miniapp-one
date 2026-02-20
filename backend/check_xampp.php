<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Quick check for XAMPP paths
$paths = [
    'D:/next/xampp/htdocs',
    'D:/next/xampp/apache/conf/httpd.conf'
];
foreach($paths as $p) {
    echo $p . ' => ' . (file_exists($p) ? 'EXISTS' : 'NOT FOUND') . PHP_EOL;
}

// Try to create symlink or copy backend to htdocs for HTTP testing
$htdocs = 'D:/next/xampp/htdocs';
if (is_dir($htdocs)) {
    $target = $htdocs . '/paxyo';
    if (!file_exists($target)) {
        echo "Backend not in htdocs. Creating symlink...\n";
        // On Windows, use junction
        echo "HTDOCS_PATH=$htdocs\n";
    } else {
        echo "Backend already accessible at $target\n";
    }
}

// Test HTTP connectivity
echo "\nTesting localhost HTTP:\n";
$ch = curl_init('http://localhost/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
curl_setopt($ch, CURLOPT_NOBODY, true);
$result = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);
echo "HTTP Status: $code" . ($error ? " (Error: $error)" : " - Apache is running!") . "\n";
?>
