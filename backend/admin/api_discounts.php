<?php
// admin/api_discounts.php - Holiday & Discount Management
session_start();
include '../db.php';

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get Active Holiday (for frontend usage, public endpoint might be needed separately or this one used)
// Actually this is admin API. Public check should be in a general API or embedded in smm.php

// Get List
if ($action === 'get_holidays') {
    $result = mysqli_query($conn, "SELECT * FROM holidays ORDER BY start_date DESC");
    $holidays = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $holidays[] = $row;
    }
    echo json_encode(['success' => true, 'holidays' => $holidays]);
    exit;
}

// Add Holiday
if ($action === 'add_holiday') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $start = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end = mysqli_real_escape_string($conn, $_POST['end_date']);
    $percent = floatval($_POST['percent']);
    
    if (!$name || !$start || !$end) {
        echo json_encode(['success' => false, 'error' => 'Missing fields']);
        exit;
    }
    
    $query = "INSERT INTO holidays (name, start_date, end_date, discount_percent, status) VALUES ('$name', '$start', '$end', $percent, 'active')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Holiday added']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// Update Status
if ($action === 'toggle_status') {
    $id = intval($_POST['id']);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    
    mysqli_query($conn, "UPDATE holidays SET status = '$status' WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// Delete
if ($action === 'delete_holiday') {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM holidays WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
