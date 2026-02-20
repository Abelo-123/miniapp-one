<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// webhook_handler.php - Receives webhooks from GodOfPanel API
// Configure this URL in your GodOfPanel dashboard: https://yoursite.com/webhook_handler.php

require_once 'db.php';
require_once 'order_manager.php';

// Log all incoming requests
$raw_input = file_get_contents('php://input');
error_log("Webhook received: " . $raw_input);

// Parse the webhook data
$webhook_data = json_decode($raw_input, true);

if (!$webhook_data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Extract order information from webhook
// Note: Adjust these fields based on actual GodOfPanel webhook format
$api_order_id = $webhook_data['order_id'] ?? $webhook_data['order'] ?? null;
$new_status = strtolower($webhook_data['status'] ?? '');
$start_count = intval($webhook_data['start_count'] ?? 0);
$remains = intval($webhook_data['remains'] ?? 0);

if (!$api_order_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order_id']);
    exit;
}

// Find the order in our database
$stmt = $conn->prepare("SELECT id, user_id, status, charge, quantity FROM orders WHERE api_order_id = ?");
$stmt->bind_param("s", $api_order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    error_log("Order not found: $api_order_id");
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$old_status = $order['status'];
$user_id = $order['user_id'];
$local_id = $order['id'];

// Update the order status
$update_stmt = $conn->prepare("UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE id = ?");
$update_stmt->bind_param("siii", $new_status, $start_count, $remains, $local_id);
$update_stmt->execute();

// Handle refunds if status changed
$refund_amount = 0;
$refund_type = null;

if ($new_status !== $old_status) {
    if ($new_status === 'canceled' || $new_status === 'cancelled') {
        // Full refund
        $refund_amount = floatval($order['charge']);
        $refund_type = 'cancelled';
        // Update status to 'cancelled' for consistency
        $new_status = 'cancelled';
        
        $conn->query("UPDATE auth SET balance = balance + $refund_amount WHERE tg_id = $user_id");
        error_log("WEBHOOK REFUND: $refund_amount for Order #$local_id (Cancelled)");
        
    } elseif ($new_status === 'partial') {
        // Partial refund based on remains
        if ($order['quantity'] > 0) {
            $refund_amount = ($order['charge'] * ($remains / $order['quantity']));
            $refund_type = 'partial';
            
            $conn->query("UPDATE auth SET balance = balance + $refund_amount WHERE tg_id = $user_id");
            error_log("WEBHOOK REFUND: $refund_amount for Order #$local_id (Partial, $remains remains)");
        }
    }
}

// Notify WebSocket clients about the update
$ws_message = json_encode([
    'type' => 'order_status_changed',
    'order_id' => $local_id,
    'api_order_id' => $api_order_id,
    'old_status' => $old_status,
    'new_status' => $new_status,
    'remains' => $remains,
    'start_count' => $start_count,
    'refund_amount' => $refund_amount,
    'refund_type' => $refund_type,
    'user_id' => $user_id
]);

// Send to WebSocket server via internal socket
try {
    $ws_client = @stream_socket_client('tcp://127.0.0.1:8080', $errno, $errstr, 1);
    if ($ws_client) {
        fwrite($ws_client, $ws_message);
        fclose($ws_client);
    }
} catch (Exception $e) {
    error_log("Failed to notify WebSocket: " . $e->getMessage());
}

// Return success
http_response_code(200);
echo json_encode([
    'success' => true,
    'order_id' => $local_id,
    'status' => $new_status,
    'refund_amount' => $refund_amount
]);
?>
