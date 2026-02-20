<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// admin/realtime_admin_stream.php - Real-time SSE for Admin Dashboard
// Provides live updates for dashboard stats and charts

// IMPORTANT: This file requires admin authentication
// If you're getting 500 errors, check error_log in xampp/apache/logs/

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

// Disable output buffering for SSE
while (ob_get_level() > 0) {
    ob_end_clean();
}


// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
    @session_start();
}

// Check admin authentication
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    echo "data: " . json_encode(['error' => 'Unauthorized - Please login first']) . "\n\n";
    flush();
    exit;
}

// Close session to allow other requests
session_write_close();

// Include database connection
try {
    require_once '../db.php';
} catch (Exception $e) {
    echo "data: " . json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]) . "\n\n";
    flush();
    exit;
}

// Verify database connection
if (!isset($conn) || !$conn) {
    echo "data: " . json_encode(['error' => 'Database not connected']) . "\n\n";
    flush();
    exit;
}

// Use temp directory for state tracking
$temp_dir = __DIR__ . '/../temp';
if (!is_dir($temp_dir)) {
    @mkdir($temp_dir, 0755, true);
}

$state_file = $temp_dir . "/admin_dashboard_state.json";
$last_state = [];

if (file_exists($state_file)) {
    $content = @file_get_contents($state_file);
    if ($content) {
        $last_state = json_decode($content, true) ?? [];
    }
}

$start_time = time();
$max_duration = 30; // Run for 30 seconds then reconnect

// Helper: Get current state hash
function getAdminState($conn) {
    $state = [];
    
    try {
        // Stats hash
        $result = @mysqli_query($conn, "
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status != 'cancelled' THEN charge ELSE 0 END) as total_revenue
            FROM orders
        ");
        
        if ($result) {
            $stats = mysqli_fetch_assoc($result);
            $state['stats_hash'] = md5(json_encode($stats));
        } else {
            $state['stats_hash'] = md5('0');
        }
        
        // Orders hash
        // Orders hash
        $result = @mysqli_query($conn, "
            SELECT GROUP_CONCAT(CONCAT(id,'|',status) ORDER BY id DESC SEPARATOR ',') as hash
            FROM (
                SELECT id, status FROM orders ORDER BY id DESC LIMIT 50
            ) as subq
        ");
        
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $state['orders_hash'] = md5($row['hash'] ?? '');
        } else {
            $state['orders_hash'] = md5('0');
        }
        
        return $state;
    } catch (Exception $e) {
        error_log("getAdminState error: " . $e->getMessage());
        return ['stats_hash' => md5('0'), 'orders_hash' => md5('0')];
    }
}

// Helper: Get full admin data
function getFullAdminData($conn) {
    $data = [];
    
    try {
        // Stats
        $result = @mysqli_query($conn, "
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status != 'cancelled' THEN charge ELSE 0 END) as total_revenue
            FROM orders
        ");
        
        if ($result) {
            $data['stats'] = mysqli_fetch_assoc($result);
        } else {
            $data['stats'] = [
                'total_orders' => 0,
                'pending_orders' => 0,
                'completed_orders' => 0,
                'total_revenue' => 0
            ];
        }
        
        // Chart data (last 7 days)
        $days = 7;
        $dates = [];
        $orders_data = [];
        $revenue_data = [];
        $users_data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[] = date('M d', strtotime($date));
            
            // Orders & Revenue
            $q = @mysqli_query($conn, "
                SELECT COUNT(*) as count, SUM(charge) as revenue 
                FROM orders 
                WHERE DATE(created_at) = '$date' AND status != 'cancelled'
            ");
            
            if ($q) {
                $res = mysqli_fetch_assoc($q);
                $orders_data[] = intval($res['count']);
                $revenue_data[] = floatval($res['revenue'] ?? 0);
            } else {
                $orders_data[] = 0;
                $revenue_data[] = 0;
            }
            
            // New Users
            $u = @mysqli_query($conn, "SELECT COUNT(*) as count FROM auth WHERE DATE(created_at) = '$date'");
            if ($u) {
                $users_data[] = intval(mysqli_fetch_assoc($u)['count']);
            } else {
                $users_data[] = 0;
            }
        }
        
        $data['charts'] = [
            'labels' => $dates,
            'datasets' => [
                'orders' => $orders_data,
                'revenue' => $revenue_data,
                'users' => $users_data
            ]
        ];
        
        return $data;
    } catch (Exception $e) {
        error_log("getFullAdminData error: " . $e->getMessage());
        return [
            'stats' => [
                'total_orders' => 0,
                'pending_orders' => 0,
                'completed_orders' => 0,
                'total_revenue' => 0
            ],
            'charts' => [
                'labels' => [],
                'datasets' => [
                    'orders' => [],
                    'revenue' => [],
                    'users' => []
                ]
            ]
        ];
    }
}

// Send initial connection message
echo "data: " . json_encode(['type' => 'connected', 'message' => 'Admin stream connected']) . "\n\n";
flush();

// Main loop - Check for changes every 5 seconds
while (time() - $start_time < $max_duration) {
    // Check connection first
    if (connection_aborted()) {
        break;
    }
    
    try {
        $current_state = getAdminState($conn);
        
        // Check if ANYTHING changed
        $changed = false;
        $changes = [];
        
        if (!isset($last_state['stats_hash']) || $current_state['stats_hash'] !== $last_state['stats_hash']) {
            $changed = true;
            $changes[] = 'stats';
        }
        
        if (!isset($last_state['orders_hash']) || $current_state['orders_hash'] !== $last_state['orders_hash']) {
            $changed = true;
            $changes[] = 'orders';
            $changes[] = 'charts';
        }
        
        if ($changed) {
            // Something changed! Send full update
            $full_data = getFullAdminData($conn);
            
            $payload = [
                'type' => 'update',
                'timestamp' => time(),
                'changes' => $changes,
                'data' => $full_data
            ];
            
            echo "data: " . json_encode($payload) . "\n\n";
            @ob_flush();
            flush();
            
            // Save current state
            $last_state = $current_state;
            @file_put_contents($state_file, json_encode($current_state));
            
        } else {
            // Nothing changed, send heartbeat
            echo "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => time()]) . "\n\n";
            @ob_flush();
            flush();
        }
        
        sleep(5); // Check every 5 seconds
        
        
    } catch (Exception $e) {
        error_log("SSE Loop error: " . $e->getMessage());
        echo "data: " . json_encode(['type' => 'error', 'message' => 'Internal error']) . "\n\n";
        flush();
        sleep(5);
    }
}

// Clean up
if (isset($current_state)) {
    @file_put_contents($state_file, json_encode($current_state));
}
?>
