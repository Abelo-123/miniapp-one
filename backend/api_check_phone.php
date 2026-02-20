<?php
/**
 * api_check_phone.php - Check if user has phone number on record
 */
require_once 'db.php';

// Ensure phone_number column exists
mysqli_query($conn, "ALTER TABLE auth ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) DEFAULT NULL");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$tg_id = $_GET['tg_id'] ?? '';

if (empty($tg_id)) {
    echo json_encode(['has_phone' => false, 'error' => 'No tg_id provided']);
    exit;
}

$tg_id = mysqli_real_escape_string($conn, $tg_id);
$result = mysqli_query($conn, "SELECT phone_number FROM auth WHERE tg_id = '$tg_id'");

if ($row = mysqli_fetch_assoc($result)) {
    $has_phone = !empty($row['phone_number']);
    echo json_encode(['has_phone' => $has_phone, 'phone' => $has_phone ? $row['phone_number'] : null]);
} else {
    echo json_encode(['has_phone' => false]);
}
