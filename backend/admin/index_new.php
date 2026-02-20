<?php
// admin/index_new.php - Redesigned Admin Panel
session_start();
include '../db.php';

// Simple auth check (you should implement proper admin authentication)
// For now, we'll just use a simple password check
$isAuthenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

if (!$isAuthenticated && isset($_POST['admin_password'])) {
    // Simple password check - CHANGE THIS IN PRODUCTION!
    if ($_POST['admin_password'] === 'admin123') {
        $_SESSION['admin_authenticated'] = true;
        $isAuthenticated = true;
    }
}

if (!$isAuthenticated) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Paxyo SMM</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body { background: #0a0a0f; }
        </style>
    </head>
    <body class="flex items-center justify-center min-h-screen">
        <div class="bg-gray-900 p-8 rounded-lg border border-gray-800 w-full max-w-md">
            <h1 class="text-2xl font-bold text-white mb-6 text-center">Admin Login</h1>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-400 mb-2">Password</label>
                    <input type="password" name="admin_password" class="w-full p-3 bg-gray-800 border border-gray-700 rounded text-white focus:outline-none focus:border-purple-500" required>
                </div>
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded transition-colors">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    
    if ($action === 'cancel_order') {
        $order_id = intval($_POST['order_id']);
        // Get order details
        $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));
        if ($order) {
            // Refund the amount
            $user_id = $order['user_id'];
            $refund = $order['charge'];
            mysqli_query($conn, "UPDATE auth SET balance = balance + $refund WHERE tg_id = $user_id");
            mysqli_query($conn, "UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
            echo json_encode(['success' => true, 'message' => 'Order cancelled and refunded']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
        }
        exit;
    }
    
    if ($action === 'get_orders') {
        $search = isset($_POST['search']) ? mysqli_real_escape_string($conn, $_POST['search']) : '';
        $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        
        $where = [];
        if ($search) {
            $where[] = "(id LIKE '%$search%' OR service_id LIKE '%$search%' OR user_id LIKE '%$search%' OR link LIKE '%$search%')";
        }
        if ($status && $status !== 'all') {
            $where[] = "status = '$status'";
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT * FROM orders $whereClause ORDER BY created_at DESC LIMIT $limit";
        $result = mysqli_query($conn, $query);
        
        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
        
        echo json_encode(['success' => true, 'orders' => $orders]);
        exit;
    }
    
    if ($action === 'get_stats') {
        $stats = [];
        $stats['total_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
        $stats['pending_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'processing')"))['count'];
        $stats['completed_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'completed'"))['count'];
        $stats['cancelled_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'"))['count'];
        $stats['total_users'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM auth"))['count'];
        $stats['total_revenue'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(charge) as total FROM orders WHERE status = 'completed'"))['total'] ?? 0;
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }
}

// Get initial data
$recommended = [];
$result = mysqli_query($conn, "SELECT service_id FROM admin_recommended_services");
while ($row = mysqli_fetch_assoc($result)) {
    $recommended[] = intval($row['service_id']);
}

$marquee_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('marquee_text', 'marquee_enabled')");
$settings = [];
while ($row = mysqli_fetch_assoc($marquee_res)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$marquee_text = $settings['marquee_text'] ?? 'Welcome to Paxyo SMM!';
$marquee_enabled = ($settings['marquee_enabled'] ?? '1') === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Paxyo SMM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../smm_styles.css">
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: #1a1a24;
            --bg-input: #0d0d12;
            --text-primary: #ffffff;
            --text-secondary: #8b8b9e;
            --text-muted: #5a5a6e;
            --accent-primary: #6c5ce7;
            --accent-success: #00d26a;
            --accent-warning: #ffc107;
            --accent-danger: #ff4757;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 92, 231, 0.15);
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .tab-btn:hover {
            color: var(--text-primary);
        }
        
        .tab-btn.active {
            color: var(--accent-primary);
            border-bottom-color: var(--accent-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--accent-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #5b4cd6;
        }
        
        .btn-danger {
            background: var(--accent-danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #e63946;
        }
        
        .btn-success {
            background: var(--accent-success);
            color: white;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-pending { background: rgba(255, 171, 0, 0.15); color: #ffab00; }
        .status-processing { background: rgba(33, 150, 243, 0.15); color: #2196f3; }
        .status-completed { background: rgba(0, 210, 106, 0.15); color: #00d26a; }
        .status-cancelled { background: rgba(255, 71, 87, 0.15); color: #ff4757; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
                <p class="text-gray-400 mt-1">Paxyo SMM Management Panel</p>
            </div>
            <button onclick="logout()" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-white font-medium">
                Logout
            </button>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 mb-6">
            <div class="flex overflow-x-auto">
                <button class="tab-btn active" onclick="switchTab('dashboard')">📊 Dashboard</button>
                <button class="tab-btn" onclick="switchTab('orders')">📦 Orders</button>
                <button class="tab-btn" onclick="switchTab('services')">⚙️ Services</button>
                <button class="tab-btn" onclick="switchTab('alerts')">🔔 Alerts</button>
                <button class="tab-btn" onclick="switchTab('settings')">⚙️ Settings</button>
            </div>
        </div>

        <!-- Dashboard Tab -->
        <div id="tab-dashboard" class="tab-content active">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="stat-card">
                    <div class="text-gray-400 text-sm mb-2">Total Orders</div>
                    <div class="text-3xl font-bold" id="stat-total-orders">-</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray-400 text-sm mb-2">Pending Orders</div>
                    <div class="text-3xl font-bold text-yellow-500" id="stat-pending-orders">-</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray-400 text-sm mb-2">Completed</div>
                    <div class="text-3xl font-bold text-green-500" id="stat-completed-orders">-</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray-400 text-sm mb-2">Total Revenue</div>
                    <div class="text-3xl font-bold text-purple-500" id="stat-revenue">0 ETB</div>
                </div>
            </div>
            
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h2 class="text-xl font-bold mb-4">Recent Activity</h2>
                <p class="text-gray-400">Dashboard overview coming soon...</p>
            </div>
        </div>

        <!-- Orders Tab -->
        <div id="tab-orders" class="tab-content">
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mb-6">
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                    <input type="text" id="order-search" placeholder="Search by ID, User ID, Service ID..." class="flex-1 p-3 bg-gray-800 border border-gray-700 rounded text-white focus:outline-none focus:border-purple-500">
                    <select id="order-status-filter" class="p-3 bg-gray-800 border border-gray-700 rounded text-white focus:outline-none focus:border-purple-500">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button onclick="searchOrders()" class="bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded font-bold whitespace-nowrap">Search</button>
                </div>
            </div>

            <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User ID</th>
                                <th>Service ID</th>
                                <th>Quantity</th>
                                <th>Charge</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table-body">
                            <tr>
                                <td colspan="8" class="text-center text-gray-500 py-8">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Services Tab -->
        <div id="tab-services" class="tab-content">
            <p class="text-gray-400">Services management (use existing admin features)</p>
        </div>

        <!-- Alerts Tab -->
        <div id="tab-alerts" class="tab-content">
            <p class="text-gray-400">Alerts management (use existing admin features)</p>
        </div>

        <!-- Settings Tab -->
        <div id="tab-settings" class="tab-content">
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h2 class="text-xl font-bold mb-4">Marquee Settings</h2>
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="marquee-enabled" <?php echo $marquee_enabled ? 'checked' : ''; ?> class="w-5 h-5">
                        <label for="marquee-enabled">Enable Marquee</label>
                    </div>
                    <input type="text" id="marquee-text" value="<?php echo htmlspecialchars($marquee_text); ?>" class="w-full p-3 bg-gray-800 border border-gray-700 rounded text-white">
                    <button onclick="saveMarquee()" class="btn btn-primary">Save Marquee</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Update buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update content
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Load data for specific tabs
            if (tabName === 'dashboard') loadStats();
            if (tabName === 'orders') searchOrders();
        }

        async function loadStats() {
            const formData = new FormData();
            formData.append('ajax_action', 'get_stats');
            
            const res = await fetch('', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                document.getElementById('stat-total-orders').textContent = data.stats.total_orders;
                document.getElementById('stat-pending-orders').textContent = data.stats.pending_orders;
                document.getElementById('stat-completed-orders').textContent = data.stats.completed_orders;
                document.getElementById('stat-revenue').textContent = '$' + parseFloat(data.stats.total_revenue).toFixed(2);
            }
        }

        async function searchOrders() {
            const search = document.getElementById('order-search').value;
            const status = document.getElementById('order-status-filter').value;
            
            const formData = new FormData();
            formData.append('ajax_action', 'get_orders');
            formData.append('search', search);
            formData.append('status', status);
            
            const res = await fetch('', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                renderOrders(data.orders);
            }
        }

        function renderOrders(orders) {
            const tbody = document.getElementById('orders-table-body');
            
            if (orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-8">No orders found</td></tr>';
                return;
            }
            
            tbody.innerHTML = orders.map(order => `
                <tr>
                    <td class="font-mono text-sm">#${order.id}</td>
                    <td class="font-mono text-sm">${order.user_id}</td>
                    <td class="font-mono text-sm">${order.service_id}</td>
                    <td>${order.quantity}</td>
                    <td class="font-mono">$${parseFloat(order.charge).toFixed(4)}</td>
                    <td><span class="status-badge status-${order.status}">${order.status}</span></td>
                    <td class="text-sm text-gray-400">${new Date(order.created_at).toLocaleString()}</td>
                    <td>
                        ${order.status !== 'cancelled' && order.status !== 'completed' ? 
                            `<button onclick="cancelOrder(${order.id})" class="btn btn-danger text-xs">Cancel</button>` : 
                            '-'}
                    </td>
                </tr>
            `).join('');
        }

        async function cancelOrder(orderId) {
            if (!confirm('Are you sure you want to cancel this order? The user will be refunded.')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'cancel_order');
            formData.append('order_id', orderId);
            
            const res = await fetch('', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                alert(data.message);
                searchOrders();
            } else {
                alert('Error: ' + data.message);
            }
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '?logout=1';
            }
        }

        // Initialize
        loadStats();
    </script>
</body>
</html>
