<?php
/**
 * api_save_phone.php - Save user's phone number
 */
require_once 'db.php';

// Ensure phone_number column exists
mysqli_query($conn, "ALTER TABLE auth ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) DEFAULT NULL");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$tg_id = $input['tg_id'] ?? '';
$phone_number = $input['phone_number'] ?? '';

if (empty($tg_id) || empty($phone_number)) {
    echo json_encode(['success' => false, 'error' => 'Missing tg_id or phone_number']);
    exit;
}

$tg_id = mysqli_real_escape_string($conn, $tg_id);
$phone_number = mysqli_real_escape_string($conn, $phone_number);

// Update the user's phone number
$result = mysqli_query($conn, "UPDATE auth SET phone_number = '$phone_number' WHERE tg_id = '$tg_id'");

if ($result) {
    if (mysqli_affected_rows($conn) > 0) {
        echo json_encode(['success' => true, 'message' => 'Phone number saved']);
    } else {
        // User might not exist yet, try to insert
        mysqli_query($conn, "INSERT INTO auth (tg_id, phone_number, created_at) VALUES ('$tg_id', '$phone_number', NOW()) ON DUPLICATE KEY UPDATE phone_number = '$phone_number'");
        echo json_encode(['success' => true, 'message' => 'Phone number saved (insert)']);
    }
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
