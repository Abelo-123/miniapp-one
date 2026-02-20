<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// order_manager.php

function syncOrderStatuses($user_id, $conn) {
    require_once 'utils_bot.php';
    // Select active orders or those with unknown status
    $stmt = $conn->prepare("SELECT id, api_order_id, service_id, status FROM orders WHERE user_id = ? AND (status IN ('pending', 'processing', 'in_progress') OR status = '' OR status IS NULL) ORDER BY created_at DESC LIMIT 20");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders_to_check = [];
    $order_ids = [];

    while ($row = $result->fetch_assoc()) {
        $orders_to_check[] = $row;
        $order_ids[] = $row['api_order_id'];
    }

    if (empty($order_ids)) {
        return ['checked' => 0, 'updated' => 0, 'updates' => []];
    }

    // Call API
    $apiKey = 'YOUR_GODOFPANEL_API_KEY';
    $apiUrl = "https://godofpanel.com/api/v2";
    $orders_str = implode(',', $order_ids);

    $postData = [
        'key' => $apiKey,
        'action' => 'status',
        'orders' => $orders_str
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    curl_close($ch);

    $api_data = json_decode($response, true);
    $updated_count = 0;
    $updates_list = [];

    if ($api_data) {
        foreach ($api_data as $api_order_id => $info) {
            if (isset($info['status'])) {
                $new_status = strtolower($info['status']);
                
                // Treat empty or unknown status from API as completed
                if ($new_status === '' || $new_status === 'unknown') {
                    $new_status = 'completed';
                }

                $start_count = intval($info['start_count'] ?? 0);
                $remains = intval($info['remains'] ?? 0);

                // Find local ID
                $local_id = null;
                $old_status = null;
                foreach ($orders_to_check as $local) {
                    if ($local['api_order_id'] == $api_order_id) {
                        $local_id = $local['id'];
                        $old_status = $local['status'];
                        break;
                    }
                }

                if ($local_id) {
                    // Check if status changed or data updated
                    // We always update to keep counts fresh, but we track status change for refund logic
                    // Normalize statuses for comparison
                    $new_status_norm = strtolower(trim($new_status));
                    $old_status_norm = strtolower(trim($old_status));
                    
                    if ($new_status_norm !== $old_status_norm) {
                        $updated_count++;
                        $updates_list[] = [
                            'id' => $local_id,
                            'old_status' => $old_status,
                            'new_status' => $new_status,
                            'remains' => $remains
                        ];
                        
                            // Refund Logic - Only on status CHANGE
                        if ($new_status_norm === 'canceled' || $new_status_norm === 'cancelled' || $new_status_norm === 'refunded') {
                            $ord = $conn->query("SELECT charge FROM orders WHERE id = $local_id")->fetch_assoc();
                            if ($ord && floatval($ord['charge']) > 0) {
                                $refund_amount = floatval($ord['charge']);
                                $conn->query("UPDATE auth SET balance = balance + $refund_amount WHERE tg_id = $user_id");
                                error_log("Refunded $refund_amount for Order #$local_id (Cancelled/Refunded)");
                                // Update status to 'cancelled' for consistency in our system
                                $new_status = 'cancelled';

                                // Notify Bot: Full Refund
                                notify_bot_admin([
                                    'type' => 'refund',
                                    'uid' => $user_id,
                                    'order' => $local_id,
                                    'amount' => $refund_amount
                                ]);
                            }
                        } elseif ($new_status_norm === 'partial') {
                            $ord = $conn->query("SELECT charge, quantity FROM orders WHERE id = $local_id")->fetch_assoc();
                            if ($ord && $ord['quantity'] > 0 && floatval($ord['charge']) > 0) {
                                // Calculate refund based on remaining items
                                $safe_remains = max(0, intval($remains));
                                // Make sure we don't refund more than the original charge
                                $ratio = min(1, $safe_remains / $ord['quantity']);
                                $refund_amount = floatval($ord['charge']) * $ratio;
                                
                                if ($refund_amount > 0) {
                                    $conn->query("UPDATE auth SET balance = balance + $refund_amount WHERE tg_id = $user_id");
                                    error_log("Refunded $refund_amount for Order #$local_id (Partial: $safe_remains remains)");

                                    // Notify Bot: Partial Refund
                                    notify_bot_admin([
                                        'type' => 'partial',
                                        'uid' => $user_id,
                                        'order' => $local_id,
                                        'amount' => number_format($refund_amount, 2),
                                        'uuid' => $safe_remains // "Remains"
                                    ]);
                                }
                            }
                        }
                    }

                    $update_stmt = $conn->prepare("UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("siii", $new_status, $start_count, $remains, $local_id);
                    $update_stmt->execute();
                }
            }
        }
    }

    return ['checked' => count($order_ids), 'updated' => $updated_count, 'updates' => $updates_list];
}
?>
