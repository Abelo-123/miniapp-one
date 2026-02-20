<?php
// admin/api_orders.php - Order Management API
session_start();
include '../db.php';

// Simple auth check
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get Orders
if ($action === 'get_orders') {
    $search = isset($_POST['search']) ? mysqli_real_escape_string($conn, $_POST['search']) : '';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'all';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    
    $where = [];
    
    if ($search) {
        $where[] = "(
            orders.id LIKE '%$search%' OR 
            orders.service_id LIKE '%$search%' OR 
            orders.user_id LIKE '%$search%' OR 
            orders.link LIKE '%$search%' OR
            orders.service_name LIKE '%$search%'
        )";
    }
    
    if ($status && $status !== 'all') {
        $where[] = "orders.status = '$status'";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $query = "
        SELECT 
            orders.*,
            auth.tg_username as user_name,
            auth.balance as user_balance
        FROM orders 
        LEFT JOIN auth ON orders.user_id = auth.tg_id
        $whereClause 
        ORDER BY orders.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $result = mysqli_query($conn, $query);
    $orders = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM orders $whereClause";
    $countResult = mysqli_query($conn, $countQuery);
    $total = mysqli_fetch_assoc($countResult)['total'];
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => $total,
        'showing' => count($orders)
    ]);
    exit;
}

// Cancel Order
if ($action === 'cancel_order') {
    $order_id = intval($_POST['order_id']);
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : 'Admin cancelled';
    
    // Get order details
    $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    if ($order['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'error' => 'Order already cancelled']);
        exit;
    }
    
    if ($order['status'] === 'completed') {
        echo json_encode(['success' => false, 'error' => 'Cannot cancel completed order']);
        exit;
    }
    
    // Refund the amount
    $user_id = $order['user_id'];
    $refund = floatval($order['charge']);
    
    mysqli_query($conn, "UPDATE auth SET balance = balance + $refund WHERE tg_id = $user_id");
    mysqli_query($conn, "UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
    
    // Log the action
    mysqli_query($conn, "
        INSERT INTO order_logs (order_id, action, old_status, new_status, admin_note) 
        VALUES ($order_id, 'cancel', '{$order['status']}', 'cancelled', '$reason')
    ");
    
    echo json_encode([
        'success' => true,
        'message' => "Order #$order_id cancelled. User refunded $$refund"
    ]);
    exit;
}

// Get Order Details
if ($action === 'get_order_details') {
    $order_id = intval($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
    
    $order = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            orders.*,
            auth.tg_username as user_name,
            auth.balance as user_balance,
            auth.tg_username as user_username
        FROM orders 
        LEFT JOIN auth ON orders.user_id = auth.tg_id
        WHERE orders.id = $order_id
    "));
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    // Get order logs
    $logs = [];
    $logsResult = mysqli_query($conn, "SELECT * FROM order_logs WHERE order_id = $order_id ORDER BY created_at DESC");
    while ($row = mysqli_fetch_assoc($logsResult)) {
        $logs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'logs' => $logs
    ]);
    exit;
}

// Get Statistics
if ($action === 'get_stats') {
    $stats = [];
    
    $stats['total_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
    $stats['pending_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'processing')"))['count'];
    $stats['completed_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'completed'"))['count'];
    $stats['cancelled_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'"))['count'];
    $stats['total_users'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM auth"))['count'];
    $stats['total_revenue'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(charge) as total FROM orders WHERE status = 'completed'"))['total'] ?? 0;
    $stats['pending_revenue'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(charge) as total FROM orders WHERE status IN ('pending', 'processing')"))['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    exit;
}

// Bulk Cancel Orders
if ($action === 'bulk_cancel') {
    $order_ids = $_POST['order_ids'] ?? [];
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : 'Bulk admin cancellation';
    
    if (empty($order_ids) || !is_array($order_ids)) {
        echo json_encode(['success' => false, 'error' => 'No orders selected']);
        exit;
    }
    
    $cancelled = 0;
    $errors = [];
    
    foreach ($order_ids as $order_id) {
        $order_id = intval($order_id);
        $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));
        
        if (!$order || $order['status'] === 'cancelled' || $order['status'] === 'completed') {
            $errors[] = "Order #$order_id cannot be cancelled";
            continue;
        }
        
        $user_id = $order['user_id'];
        $refund = floatval($order['charge']);
        
        mysqli_query($conn, "UPDATE auth SET balance = balance + $refund WHERE tg_id = $user_id");
        mysqli_query($conn, "UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
        mysqli_query($conn, "
            INSERT INTO order_logs (order_id, action, old_status, new_status, admin_note) 
            VALUES ($order_id, 'bulk_cancel', '{$order['status']}', 'cancelled', '$reason')
        ");
        
        $cancelled++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Cancelled $cancelled orders",
        'errors' => $errors
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
