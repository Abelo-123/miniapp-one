<?php
// admin/api_deposit_analytics.php - Deposit Analytics API
error_reporting(E_ALL);
ini_set('display_errors', 0); // Suppress HTML errors in JSON response
session_start();
include '../db.php';

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get Deposit Charts Data
if ($action === 'get_deposit_charts') {
    $period = $_GET['period'] ?? '7days'; // 7days, 30days, 90days
    
    $days = 7;
    if ($period === '30days') $days = 30;
    if ($period === '90days') $days = 90;
    
    $dates = [];
    $deposit_counts = [];
    $deposit_amounts = [];
    $success_rates = [];
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('M d', strtotime($date));
        
        // Get deposit stats for this day
        // Check for both 'success' and 'completed' statuses to be safe
        $query = "
            SELECT 
                COUNT(*) as total_deposits,
                SUM(CASE WHEN status IN ('success', 'completed') THEN 1 ELSE 0 END) as successful_deposits,
                SUM(CASE WHEN status IN ('success', 'completed') THEN CAST(amount AS DECIMAL(10,2)) ELSE 0 END) as total_amount
            FROM deposits
            WHERE DATE(created_at) = '$date'
        ";
        
        $result = @mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $total = intval($row['total_deposits']);
            $successful = intval($row['successful_deposits']);
            $amount = floatval($row['total_amount'] ?? 0);
            
           $deposit_counts[] = $total;
            $deposit_amounts[] = $amount;
            $success_rates[] = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
        } else {
            $deposit_counts[] = 0;
            $deposit_amounts[] = 0;
            $success_rates[] = 0;
        }
    }
    
    // Get overall deposit stats
    $stats_query = "
        SELECT 
            COUNT(*) as total_deposits,
            SUM(CASE WHEN status IN ('success', 'completed') THEN 1 ELSE 0 END) as successful_deposits,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_deposits,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_deposits,
            SUM(CASE WHEN status IN ('success', 'completed') THEN CAST(amount AS DECIMAL(10,2)) ELSE 0 END) as total_revenue,
            AVG(CASE WHEN status IN ('success', 'completed') THEN CAST(amount AS DECIMAL(10,2)) ELSE NULL END) as avg_deposit
        FROM deposits
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
    ";
    
    $stats_result = @mysqli_query($conn, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);
    
    // Get top depositors
    $top_query = "
        SELECT 
            user_id,
            COUNT(*) as deposit_count,
            SUM(CAST(amount AS DECIMAL(10,2))) as total_deposited
        FROM deposits
        WHERE status IN ('success', 'completed') AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        GROUP BY user_id
        ORDER BY total_deposited DESC
        LIMIT 5
    ";
    
    $top_result = mysqli_query($conn, $top_query);
    $top_depositors = [];
    while ($row = mysqli_fetch_assoc($top_result)) {
        $top_depositors[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'charts' => [
            'labels' => $dates,
            'datasets' => [
                'deposit_counts' => $deposit_counts,
                'deposit_amounts' => $deposit_amounts,
                'success_rates' => $success_rates
            ]
        ],
        'stats' => $stats,
        'top_depositors' => $top_depositors
    ]);
    exit;
}

// Get Combined Activity (Deposits, Orders, Users)
if ($action === 'get_combined_activity' || $action === 'get_recent_deposits') { // Fallback for old calls
    $limit = intval($_GET['limit'] ?? 20);
    $activities = [];

    // 1. Deposits
    try {
        $q = "SELECT d.id, d.user_id, d.amount, d.status, d.created_at, d.reference_id, a.tg_username, 'deposit' as type 
              FROM deposits d LEFT JOIN auth a ON d.user_id = a.tg_id 
              ORDER BY d.created_at DESC LIMIT $limit";
        $res = mysqli_query($conn, $q);
        if ($res) while ($row = mysqli_fetch_assoc($res)) $activities[] = $row;
    } catch (Exception $e) {}

    // 2. Orders
    try {
        $q = "SELECT o.id, o.user_id, o.charge as amount, o.status, o.created_at, o.service_id, a.tg_username, 'order' as type 
              FROM orders o LEFT JOIN auth a ON o.user_id = a.tg_id 
              ORDER BY o.created_at DESC LIMIT $limit";
        $res = mysqli_query($conn, $q);
        if ($res) while ($row = mysqli_fetch_assoc($res)) $activities[] = $row;
    } catch (Exception $e) {}

    // 3. New Users
    try {
        $q = "SELECT id, tg_id as user_id, 0 as amount, 'active' as status, created_at, tg_username, 'user' as type 
              FROM auth 
              ORDER BY created_at DESC LIMIT $limit";
        $res = mysqli_query($conn, $q);
        if ($res) while ($row = mysqli_fetch_assoc($res)) $activities[] = $row;
    } catch (Exception $e) {}

    // Sort by Date Descending
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Slice to limit
    $activities = array_slice($activities, 0, $limit);

    echo json_encode(['success' => true, 'deposits' => $activities]); // Key 'deposits' kept for frontend compatibility
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
