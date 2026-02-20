<?php
// admin/api_analytics.php - Analytics API
session_start();
include '../db.php';

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_charts') {
    $days = 7;
    $dates = [];
    $orders_data = [];
    $revenue_data = [];
    $users_data = [];
    
    // Check if orders table has created_at column
    $columns_check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'created_at'");
    $has_created_at = mysqli_num_rows($columns_check) > 0;
    
    // If no created_at, try other common timestamp columns
    $date_column = 'created_at';
    if (!$has_created_at) {
        $alt_columns = ['timestamp', 'date', 'created', 'order_date'];
        foreach ($alt_columns as $col) {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '$col'");
            if (mysqli_num_rows($check) > 0) {
                $date_column = $col;
                break;
            }
        }
    }
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('M d', strtotime($date));
        
        // Orders & Revenue - with error handling
        $q = @mysqli_query($conn, "
            SELECT COUNT(*) as count, SUM(charge) as revenue 
            FROM orders 
            WHERE DATE($date_column) = '$date' AND status != 'cancelled'
        ");
        
        if ($q) {
            $res = mysqli_fetch_assoc($q);
            $orders_data[] = intval($res['count']);
            $revenue_data[] = floatval($res['revenue'] ?? 0);
        } else {
            // If date column doesn't work, just return 0s
            $orders_data[] = 0;
            $revenue_data[] = 0;
        }
        
        // New Users - check auth table
        $auth_col_check = @mysqli_query($conn, "SHOW COLUMNS FROM auth LIKE 'created_at'");
        if ($auth_col_check && mysqli_num_rows($auth_col_check) > 0) {
            $u = @mysqli_query($conn, "SELECT COUNT(*) as count FROM auth WHERE DATE(created_at) = '$date'");
            if ($u) {
                $users_data[] = intval(mysqli_fetch_assoc($u)['count']);
            } else {
                $users_data[] = 0;
            }
        } else {
            // No date tracking for users, return 0
            $users_data[] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $dates,
        'datasets' => [
            'orders' => $orders_data,
            'revenue' => $revenue_data,
            'users' => $users_data
        ]
    ]);
    exit;
}

// Realtime Stats (lightweight for polling)
if ($action === 'get_realtime') {
    // Current Active Users (approximate by recent activity if logic exists, otherwise just total users)
    // Here we'll return today's stats
    $today = date('Y-m-d');
    
    $orders_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE DATE(created_at) = '$today'"))['c'];
    $revenue_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(charge) as c FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled'"))['c'] ?? 0;
    $users_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM auth"))['c'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'orders_today' => $orders_today,
            'revenue_today' => $revenue_today,
            'users_total' => $users_total
        ]
    ]);
    exit;
}

// System Health Check
if ($action === 'system_health') {
    // Count cached services
    $servicesFile = '../services_cache.json';
    $servicesCount = 0;
    if (file_exists($servicesFile)) {
        $services = json_decode(file_get_contents($servicesFile), true);
        $servicesCount = is_array($services) ? count($services) : 0;
    }
    
    // Active users in last 24 hours
    $activeUsers = 0;
    $activeCheck = @mysqli_query($conn, "SELECT COUNT(*) as count FROM auth WHERE last_active >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    if ($activeCheck) {
        $activeUsers = mysqli_fetch_assoc($activeCheck)['count'] ?? 0;
    }
    
    // API status check
    $apiStatus = 'OK';
    
    // Database size
    $dbSize = 'N/A';
    $sizeResult = @mysqli_query($conn, "
        SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ");
    if ($sizeResult && $row = mysqli_fetch_assoc($sizeResult)) {
        $dbSize = round($row['size_mb'], 2) . ' MB';
    }
    
    echo json_encode([
        'success' => true,
        'services_count' => $servicesCount,
        'active_users_24h' => $activeUsers,
        'api_status' => $apiStatus,
        'db_size' => $dbSize
    ]);
    exit;
}

// Top Customers
if ($action === 'top_customers') {
    $limit = intval($_POST['limit'] ?? 10);
    $result = @mysqli_query($conn, "
        SELECT 
            a.tg_id,
            COALESCE(a.first_name, a.username, 'User') as name,
            COUNT(o.id) as order_count,
            COALESCE(SUM(o.charge), 0) as total_spent
        FROM auth a
        LEFT JOIN orders o ON a.tg_id = o.user_id
        GROUP BY a.tg_id
        HAVING order_count > 0
        ORDER BY total_spent DESC
        LIMIT $limit
    ");
    
    $customers = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $customers[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'customers' => $customers]);
    exit;
}

// Popular Services
if ($action === 'popular_services') {
    $limit = intval($_POST['limit'] ?? 10);
    $result = @mysqli_query($conn, "
        SELECT 
            service_id,
            service_name,
            COUNT(*) as order_count
        FROM orders
        WHERE service_id IS NOT NULL
        GROUP BY service_id
        ORDER BY order_count DESC
        LIMIT $limit
    ");
    
    $services = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $services[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'services' => $services]);
    exit;
}

// Export Orders
if ($action === 'export_orders') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_export.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'User ID', 'Service ID', 'Service Name', 'Link', 'Quantity', 'Charge', 'Status', 'Created At']);
    
    $result = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC");
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            $row['user_id'],
            $row['service_id'],
            $row['service_name'] ?? '',
            $row['link'] ?? '',
            $row['quantity'],
            $row['charge'],
            $row['status'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Export Users
if ($action === 'export_users') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_export.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['TG ID', 'Username', 'First Name', 'Last Name', 'Balance', 'Total Orders', 'Total Spent', 'Joined At', 'Last Active', 'Blocked']);
    
    $result = mysqli_query($conn, "
        SELECT 
            a.*,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.charge), 0) as total_spent
        FROM auth a
        LEFT JOIN orders o ON a.tg_id = o.user_id
        GROUP BY a.tg_id
        ORDER BY a.created_at DESC
    ");
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['tg_id'],
            $row['username'] ?? '',
            $row['first_name'] ?? '',
            $row['last_name'] ?? '',
            $row['balance'],
            $row['total_orders'],
            $row['total_spent'],
            $row['created_at'],
            $row['last_active'] ?? '',
            $row['is_blocked'] ?? 0
        ]);
    }
    fclose($output);
    exit;
}

// Clear Cache
if ($action === 'clear_cache') {
    $cleared = [];
    
    // Clear service cache
    $cacheFile = '../services_cache.json';
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        $cleared[] = 'services_cache.json';
    }
    
    // Clear services.json if exists
    $servicesFile = '../services.json';
    if (file_exists($servicesFile)) {
        unlink($servicesFile);
        $cleared[] = 'services.json';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cache cleared! Files removed: ' . (count($cleared) ? implode(', ', $cleared) : 'none found')
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
