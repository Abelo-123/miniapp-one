<?php
// admin/api_general.php - General Admin Actions (Services, Alerts, Marquee)
session_start();
include '../db.php';

// Auth check
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_POST['action'] ?? '';

// Helper for hidden services
$hiddenFile = '../hidden_services.json';
$getHidden = function() use ($hiddenFile) {
    if (!file_exists($hiddenFile)) return [];
    return json_decode(file_get_contents($hiddenFile), true) ?? [];
};

// --- Service Management ---

if ($action === 'add_recommended') {
    $id = intval($data['service_id']);
    mysqli_query($conn, "INSERT IGNORE INTO admin_recommended_services (service_id) VALUES ($id)");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'remove_recommended') {
    $id = intval($data['service_id']);
    mysqli_query($conn, "DELETE FROM admin_recommended_services WHERE service_id = $id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'bulk_update_recommended') {
    $ids_str = $data['ids']; 
    $ids = array_unique(array_filter(array_map('intval', explode(',', $ids_str))));
    
    mysqli_query($conn, "TRUNCATE TABLE admin_recommended_services");
    if (!empty($ids)) {
        $values = [];
        foreach ($ids as $id) {
            if ($id > 0) $values[] = "($id)";
        }
        if (!empty($values)) {
            $sql = "INSERT INTO admin_recommended_services (service_id) VALUES " . implode(',', $values);
            mysqli_query($conn, $sql);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_recommended') {
    $result = mysqli_query($conn, "SELECT service_id FROM admin_recommended_services");
    $recommended = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recommended[] = intval($row['service_id']);
    }
    echo json_encode(['success' => true, 'ids' => $recommended]);
    exit;
}

if ($action === 'hide_service') {
    $id = intval($data['service_id']);
    $hidden = $getHidden();
    if (!in_array($id, $hidden)) {
        $hidden[] = $id;
        file_put_contents($hiddenFile, json_encode(array_values($hidden)));
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'unhide_service') {
    $id = intval($data['service_id']);
    $hidden = $getHidden();
    $hidden = array_values(array_filter($hidden, fn($hid) => $hid != $id));
    file_put_contents($hiddenFile, json_encode($hidden));
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_hidden') {
    echo json_encode(['success' => true, 'ids' => $getHidden()]);
    exit;
}

if ($action === 'update_average_time') {
    $id = intval($data['service_id']);
    $time = mysqli_real_escape_string($conn, $data['average_time']);
    
    mysqli_query($conn, "INSERT INTO service_adjustments (service_id, average_time) VALUES ($id, '$time') ON DUPLICATE KEY UPDATE average_time = '$time'");
    echo json_encode(['success' => true]);
    exit;
}

// --- Deposit Management ---

if ($action === 'get_deposits') {
    // Fetch last 100 deposits with user details
    $res = mysqli_query($conn, "SELECT d.*, a.first_name FROM deposits d LEFT JOIN auth a ON d.user_id = a.tg_id ORDER BY d.created_at DESC LIMIT 100");
    $deposits = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $deposits[] = $row;
    }
    echo json_encode(['success' => true, 'deposits' => $deposits]);
    exit;
}

// --- Marquee Management ---

if ($action === 'update_marquee') {
    $text = mysqli_real_escape_string($conn, $data['text']);
    $enabled = isset($data['enabled']) && $data['enabled'] ? '1' : '0';
    
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('marquee_text', '$text') ON DUPLICATE KEY UPDATE setting_value = '$text'");
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('marquee_enabled', '$enabled') ON DUPLICATE KEY UPDATE setting_value = '$enabled'");
    
    echo json_encode(['success' => true]);
    exit;
}

// --- Alert Management ---

if ($action === 'send_alert') {
    $user_id = intval($data['user_id']);
    $message = mysqli_real_escape_string($conn, $data['message']);
    
    if ($user_id > 0 && !empty($message)) {
        mysqli_query($conn, "INSERT INTO user_alerts (user_id, message) VALUES ($user_id, '$message')");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }
    exit;
}

if ($action === 'get_user_alerts') {
    $user_id = intval($data['user_id']);
    $alerts = [];
    if ($user_id > 0) {
        $res = mysqli_query($conn, "SELECT * FROM user_alerts WHERE user_id = $user_id ORDER BY created_at DESC");
        while ($row = mysqli_fetch_assoc($res)) {
            $alerts[] = $row;
        }
    }
    echo json_encode(['success' => true, 'alerts' => $alerts]);
    exit;
}

if ($action === 'update_alert') {
    $id = intval($data['id']);
    $message = mysqli_real_escape_string($conn, $data['message']);
    mysqli_query($conn, "UPDATE user_alerts SET message = '$message' WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_alert') {
    $id = intval($data['id']);
    mysqli_query($conn, "DELETE FROM user_alerts WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// --- Rate Multiplier Management ---

if ($action === 'update_rate_multiplier') {
    $multiplier = floatval($data['multiplier']);
    if ($multiplier < 1) {
        echo json_encode(['success' => false, 'error' => 'Invalid multiplier']);
        exit;
    }
    
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('rate_multiplier', '$multiplier') ON DUPLICATE KEY UPDATE setting_value = '$multiplier'");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_settings') {
    $res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(['success' => true, 'settings' => $settings]);
    exit;
}

if ($action === 'update_maintenance') {
    $mode = isset($data['mode']) && $data['mode'] ? '1' : '0';
    $allowed_ids = mysqli_real_escape_string($conn, $data['allowed_ids'] ?? '');
    
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', '$mode') ON DUPLICATE KEY UPDATE setting_value = '$mode'");
    mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_allowed_ids', '$allowed_ids') ON DUPLICATE KEY UPDATE setting_value = '$allowed_ids'");
    
    echo json_encode(['success' => true]);
    exit;
}


echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
