<?php
// get_services.php
// Fetches services from godofpanel API with caching for performance

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Buffer output to catch any accidental echo/warnings from db.php
ob_start();
include 'db.php';
ob_end_clean();

$cacheDir = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/services.json';
$refresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

// Inject adjustments function passed by reference for speed
function inject_adjustments($services) {
    global $conn;
    $res = mysqli_query($conn, "SELECT service_id, average_time FROM service_adjustments");
    $adjustments = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $adjustments[$row['service_id']] = $row['average_time'];
        }
    }
    
    foreach ($services as &$s) {
        $id = $s['service'];
        if (isset($adjustments[$id])) {
            $s['average_time'] = $adjustments[$id];
        } else {
            $s['average_time'] = null;
        }
    }
    return $services;
}

// STRATEGY: Stale-While-Revalidate with ETag support
if (!$refresh && file_exists($cacheFile)) {
    $content = file_get_contents($cacheFile);
    $data = json_decode($content, true);
    
    if (is_array($data)) {
        $adjusted = inject_adjustments($data);
        $final_json = json_encode($adjusted);
        $etag = '"' . md5($final_json) . '"';
        
        $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($clientEtag === $etag) {
            http_response_code(304);
            exit();
        }
        
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=300, stale-while-revalidate=600');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($cacheFile)) . ' GMT');
        
        echo $final_json;
        exit();
    }
}

// Fetch fresh data from API
$apiKey = 'YOUR_GODOFPANEL_API_KEY';
$apiUrl = "https://godofpanel.com/api/v2?key=$apiKey&action=services";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data) {
            echo json_encode(inject_adjustments($data));
            curl_close($ch);
            exit();
        }
    }
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit();
}
curl_close($ch);

$data = json_decode($response, true);
if ($data === null || !is_array($data)) {
     if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data) {
            echo json_encode(inject_adjustments($data));
            exit();
        }
    }
    echo json_encode(['error' => 'Invalid API response']);
    exit();
}

// SAFETY CHECK
$threshold = 10; 
if (count($data) < $threshold && file_exists($cacheFile)) {
    $existingData = json_decode(file_get_contents($cacheFile), true);
    if (is_array($existingData) && count($existingData) > count($data)) {
        header('X-Paxyo-Cache-Fallback: true');
        echo json_encode(inject_adjustments($existingData));
        exit();
    }
}

// Save fresh to cache
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
file_put_contents($cacheFile, $response, LOCK_EX);

echo json_encode(inject_adjustments($data));
?>
