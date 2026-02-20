<?php
// admin/api_users.php - User Management API
session_start();
include '../db.php';

// Auth check
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Define "online" as active in last 12 seconds (ultra-aggressive real-time)
define('ONLINE_THRESHOLD_SECONDS', 12);

// Get Online Stats (lightweight, for real-time polling)
if ($action === 'online_stats') {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN last_seen > DATE_SUB(NOW(), INTERVAL " . ONLINE_THRESHOLD_SECONDS . " SECOND) THEN 1 ELSE 0 END) as online
            FROM auth";
    
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true,
        'total' => (int)$row['total'],
        'online' => (int)($row['online'] ?? 0)
    ]);
    exit;
}

// Get Users with online status
if ($action === 'get_users') {
    $search = isset($_POST['search']) ? mysqli_real_escape_string($conn, $_POST['search']) : '';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $sort = $_POST['sort'] ?? 'last_seen';
    $dir = $_POST['dir'] ?? 'DESC';
    $filter = $_POST['filter'] ?? 'all';

    $whereClause = "WHERE 1=1";
    if ($search) {
        $whereClause .= " AND (
            tg_id LIKE '%$search%' OR 
            username LIKE '%$search%' OR 
            first_name LIKE '%$search%' OR 
            last_name LIKE '%$search%'
        )";
    }

    if ($filter === 'online') {
        $whereClause .= " AND last_seen > DATE_SUB(NOW(), INTERVAL " . ONLINE_THRESHOLD_SECONDS . " SECOND)";
    } elseif ($filter === 'offline') {
        $whereClause .= " AND (last_seen IS NULL OR last_seen <= DATE_SUB(NOW(), INTERVAL " . ONLINE_THRESHOLD_SECONDS . " SECOND))";
    } elseif ($filter === 'blocked') {
        $whereClause .= " AND is_blocked = 1";
    } elseif ($filter === 'unblocked') {
        $whereClause .= " AND (is_blocked IS NULL OR is_blocked = 0)";
    }
    $dir = $_POST['dir'] ?? 'DESC';
    $validSorts = ['balance', 'created_at', 'last_seen', 'total_spent', 'last_deposit_at', 'total_orders'];
    if (!in_array($sort, $validSorts)) $sort = 'last_seen';
    if (!in_array($dir, ['ASC', 'DESC'])) $dir = 'DESC';

    // Base query with online status and joined deposit info
    $query = "SELECT a.*, 
                CASE 
                    WHEN a.last_seen IS NOT NULL 
                         AND a.last_seen > DATE_SUB(NOW(), INTERVAL " . ONLINE_THRESHOLD_SECONDS . " SECOND) 
                    THEN 1 
                    ELSE 0 
                END as is_online,
                d.last_deposit_at
              FROM auth a
              LEFT JOIN (
                  SELECT user_id, MAX(created_at) as last_deposit_at 
                  FROM deposits 
                  WHERE status = 'completed'
                  GROUP BY user_id
              ) d ON a.tg_id = d.user_id
              $whereClause";

    // Build Order By
    $orderBy = "ORDER BY ";
    if ($sort === 'last_deposit_at') {
        $orderBy .= "d.last_deposit_at $dir";
    } elseif ($sort === 'total_spent' || $sort === 'total_orders') {
        // These are calculated in the loop below in existing version, 
        // but for sorting we need them in the query.
        $query = "SELECT a.*, 
                    CASE WHEN a.last_seen > DATE_SUB(NOW(), INTERVAL " . ONLINE_THRESHOLD_SECONDS . " SECOND) THEN 1 ELSE 0 END as is_online,
                    d.last_deposit_at,
                    COALESCE(s.total_spent, 0) as total_spent,
                    COALESCE(s.total_orders, 0) as total_orders
                  FROM auth a
                  LEFT JOIN (
                      SELECT user_id, MAX(created_at) as last_deposit_at 
                      FROM deposits WHERE status = 'completed' GROUP BY user_id
                  ) d ON a.tg_id = d.user_id
                  LEFT JOIN (
                      SELECT user_id, SUM(charge) as total_spent, COUNT(*) as total_orders 
                      FROM orders WHERE status != 'cancelled' GROUP BY user_id
                  ) s ON a.tg_id = s.user_id
                  $whereClause";
        $orderBy .= "$sort $dir";
    } else {
        $orderBy .= "a.$sort $dir";
    }

    $finalQuery = "$query $orderBy LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $finalQuery);
    $users = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // If total_spent was already in the query (for sorting), don't refetch
        if (!isset($row['total_spent'])) {
            $uid = $row['tg_id'];
            $stats = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(charge) as total_spent
                FROM orders 
                WHERE user_id = '$uid' AND status != 'cancelled'
            "));
            $row['total_orders'] = $stats['total_orders'] ?? 0;
            $row['total_spent'] = $stats['total_spent'] ?? 0;
        }
        
        $row['is_online'] = (int)$row['is_online'] === 1;
        
        $users[] = $row;
    }
    
    // Total count and online count
    $countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM auth $whereClause");
    $total = mysqli_fetch_assoc($countResult)['total'];
    
    $onlineCount = count(array_filter($users, fn($u) => $u['is_online']));
    
    echo json_encode([
        'success' => true, 
        'users' => $users, 
        'total' => $total,
        'online_count' => $onlineCount
    ]);
    exit;
}

// Get User Detailed View (Modal)
if ($action === 'get_user_details') {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    
    // 1. Basic User Info with Online Status calculation
    $userQuery = "SELECT *, 
                CASE 
                    WHEN last_seen IS NOT NULL 
                         AND last_seen > DATE_SUB(NOW(), INTERVAL " . ONLINE_THRESHOLD_SECONDS . " SECOND) 
                    THEN 1 
                    ELSE 0 
                END as is_online 
              FROM auth WHERE tg_id = '$user_id'";
    $user = mysqli_fetch_assoc(mysqli_query($conn, $userQuery));
    
    if ($user) {
        $user['is_online'] = (int)$user['is_online'] === 1;
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // 2. Stats
    $stats = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_orders,
            SUM(charge) as total_spent,
            SUM(CASE WHEN status != 'cancelled' AND status != 'completed' THEN 1 ELSE 0 END) as active_orders
        FROM orders 
        WHERE user_id = '$user_id'
    "));
    
    // 3. Unified Activity (Orders + Deposits)
    $activity = [];
    
    // Fetch 10 recent orders
    $orderRes = mysqli_query($conn, "SELECT *, 'order' as item_type FROM orders WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($orderRes)) {
        $activity[] = [
            'type' => 'order',
            'id' => $row['id'],
            'service_id' => $row['service_id'],
            'status' => $row['status'],
            'amount' => $row['charge'],
            'date' => $row['created_at'],
            'details' => $row['link']
        ];
    }
    
    // Fetch 10 recent deposits
    $depositRes = mysqli_query($conn, "SELECT *, 'deposit' as item_type FROM deposits WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($depositRes)) {
        $activity[] = [
            'type' => 'deposit',
            'id' => $row['id'],
            'status' => $row['status'],
            'amount' => $row['amount'],
            'date' => $row['created_at'],
            'details' => $row['reference_id']
        ];
    }
    
    // Sort combined activity by date DESC
    usort($activity, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Keep top 15
    $activity = array_slice($activity, 0, 15);
    
    // 4. Audit/Action Logs (from order_logs where order belongs to user, OR user alerts)
    $logs = [];
    // Fetch logs related to this user's orders
    $logRes = mysqli_query($conn, "
        SELECT 
            l.action, 
            l.created_at, 
            l.admin_note,
            l.order_id
        FROM order_logs l
        JOIN orders o ON l.order_id = o.id
        WHERE o.user_id = '$user_id'
        ORDER BY l.created_at DESC
        LIMIT 10
    ");
    while ($logRes && $row = mysqli_fetch_assoc($logRes)) {
        $logs[] = [
            'type' => 'order_log',
            'message' => "Order #{$row['order_id']} - " . substr($row['action'], 0, 100),
            'date' => $row['created_at'],
            'note' => $row['admin_note'] ? substr($row['admin_note'], 0, 150) : null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'stats' => $stats,
        'activity' => $activity,
        'logs' => $logs
    ]);
    exit;
}

// Update User Balance
if ($action === 'update_balance') {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $type = $_POST['type'] ?? 'add'; // add or set
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    if ($type === 'add') {
        $query = "UPDATE auth SET balance = balance + $amount WHERE tg_id = '$user_id'";
    } else {
        $query = "UPDATE auth SET balance = $amount WHERE tg_id = '$user_id'";
    }
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Balance updated']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// Toggle User Block Status
if ($action === 'toggle_block') {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $is_blocked = intval($_POST['is_blocked']); // 0 = unblock, 1 = block
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    // Check if is_blocked column exists, if not create it
    $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM auth LIKE 'is_blocked'");
    if (mysqli_num_rows($checkColumn) == 0) {
        mysqli_query($conn, "ALTER TABLE auth ADD COLUMN is_blocked TINYINT(1) DEFAULT 0");
    }
    
    $query = "UPDATE auth SET is_blocked = $is_blocked WHERE tg_id = '$user_id'";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'User status updated']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
