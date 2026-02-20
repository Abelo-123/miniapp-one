<?php
// admin/index.php - New Admin Dashboard
session_start();
include '../db.php';

// Auth Logic
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_POST['admin_password'])) {
    // Simple password check - REPLACE WITH SECURE AUTH IN PRODUCTION
    if ($_POST['admin_password'] === 'admin123') {
        $_SESSION['admin_authenticated'] = true;
        
        // Notify Admin of Login
        require_once '../utils_bot.php';
        notify_bot_admin([
            'type' => 'admin_login',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);

        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid password";
    }
}

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Paxyo SMM</title>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { width: 100%; max-width: 400px; }
    </style>
</head>
<body>
    <div class="card login-card">
        <h1 class="text-center" style="margin-bottom: 24px; font-size: 24px;">Admin Login</h1>
        <?php if (isset($error)) echo "<div class='status-badge status-cancelled' style='display:block; text-align:center; margin-bottom:16px; padding:12px;'>$error</div>"; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="admin_password" class="form-input" required autofocus placeholder="Enter admin password">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Login</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paxyo Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: '#0a0a0f',
                        card: '#1a1a24',
                        accent: '#6c5ce7',
                        success: '#00b894',
                        danger: '#ff7675'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="admin_styles.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="holidays_data.js"></script>

</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="admin-title">
                <h1>Admin Dashboard</h1>
                <div class="admin-subtitle">Paxyo SMM Management Panel</div>
            </div>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="btn btn-danger btn-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </button>
            </form>
        </div>

        <!-- Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn active" data-tab="dashboard" onclick="loadTab('dashboard')">
                📊 Dashboard
            </button>
            <button class="tab-btn" data-tab="orders" onclick="loadTab('orders')">
                📦 Orders
            </button>
            <button class="tab-btn" data-tab="users" onclick="loadTab('users')">
                👥 Users
            </button>
            <button class="tab-btn" data-tab="deposits" onclick="loadTab('deposits')">
                💰 Deposits
            </button>
            <button class="tab-btn" data-tab="services" onclick="loadTab('services')">
                ⚙️ Services
            </button>
            <button class="tab-btn" data-tab="alerts" onclick="loadTab('alerts')">
                🔔 Alerts
            </button>
            <button class="tab-btn" data-tab="chat" onclick="loadTab('chat')">
                💬 Chat
            </button>
            <button class="tab-btn" data-tab="holidays" onclick="loadTab('holidays')">
                🎉 Holidays
            </button>
            <button class="tab-btn" data-tab="analytics" onclick="loadTab('analytics')">
                📈 Analytics
            </button>
            <button class="tab-btn" data-tab="bot" onclick="loadTab('bot')">
                🤖 Bot
            </button>
            <button class="tab-btn" data-tab="settings" onclick="loadTab('settings')">
                🔧 Settings
            </button>
        </div>

        <!-- Content Areas -->
        
        <!-- DASHBOARD TAB -->
        <div id="tab-dashboard" class="tab-content active">
            <div class="flex justify-between items-center mb-4">
               <h2 style="font-size: 1.2rem; font-weight: bold;">Dashboard Overview</h2>
               <button class="btn btn-primary" onclick="loadStats();">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                    Refresh Data
               </button>
            </div>

            <div class="stats-grid" style="margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value" id="dash-total-orders">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Orders</div>
                    <div class="stat-value warning" id="dash-pending-orders">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed Orders</div>
                    <div class="stat-value success" id="dash-completed-orders">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value primary" id="dash-revenue">-</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="chart-grid">
                <div class="card mb-0">
                    <div class="card-header">Performance (7 Days)</div>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="mainChart"></canvas>
                    </div>
                </div>
                <div class="card mb-0">
                    <div class="card-header">User Growth</div>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Deposit Analytics Section -->
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <span>💰 Deposit Analytics</span>
                    <select id="deposit-period" class="form-select" style="max-width: 150px;" onchange="loadDepositCharts()">
                        <option value="7days">Last 7 Days</option>
                        <option value="30days">Last 30 Days</option>
                        <option value="90days">Last 90 Days</option>
                    </select>
                </div>
                
                <!-- Deposit Stats Cards -->
                <div class="stats-grid" style="margin: 16px;">
                    <div class="stat-card">
                        <div class="stat-label">Total Deposits</div>
                        <div class="stat-value" id="deposit-total">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value success" id="deposit-successful">-</div>
                        <div class="stat-label">Successful</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value warning" id="deposit-pending">-</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value primary" id="deposit-revenue">-</div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                
                <!-- Deposit Charts -->
                <div class="chart-grid" style="padding: 16px;">
                    <div>
                        <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--text-secondary);">Deposit Trends</h3>
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="depositTrendChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--text-secondary);">Success Rate</h3>
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="depositSuccessChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Top Depositors -->
                <div style="padding: 0 16px 16px 16px;">
                    <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--text-secondary);">🏆 Top Depositors</h3>
                    <div id="top-depositors-list" style="display: grid; gap: 8px;">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
            
         
                </div>
            </div>
            

        </div>

        <!-- ORDERS TAB -->
        <div id="tab-orders" class="tab-content">
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <span>Order Management</span>
                    <button class="btn btn-secondary btn-sm" onclick="fetchOrders()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                        Refresh
                    </button>
                </div>
                
                <div class="search-bar">
                    <input type="text" id="order-search" class="form-input" placeholder="Search by Order ID, User ID, Service ID or Link...">
                    <select id="order-status" class="form-select" style="max-width: 200px;">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button class="btn btn-primary" onclick="fetchOrders()">Search</button>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Service</th>
                                <th>Link</th>
                                <th>Qty</th>
                                <th>Charge</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-list">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                <div id="orders-loading" class="loading" style="display:none;">Loading orders</div>
            </div>
        </div>

        <!-- DEPOSITS TAB -->
        <div id="tab-deposits" class="tab-content" style="display: none;">
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <span>Deposit Transactions</span>
                    <button class="btn btn-secondary btn-sm" onclick="fetchDeposits()">Refresh</button>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Ref ID</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="deposits-list">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- USERS TAB -->
        <div id="tab-users" class="tab-content">
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span>User Management</span>
                        <span id="user-count-header" class="status-badge" style="background:rgba(108, 92, 231, 0.1); color:var(--accent-primary);">0 users</span>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="fetchUsers()">Refresh</button>
                </div>
                <div class="search-bar flex-wrap">
                    <input type="text" id="user-search" class="form-input" style="flex: 2; min-width: 200px;" placeholder="Search names/IDs..." oninput="debounce(fetchUsers, 500)()">
                    
                    <select id="user-filter" class="form-select" style="flex: 0.7; min-width: 120px;" onchange="fetchUsers()">
                        <option value="all">All Status</option>
                        <option value="online">Online Now</option>
                        <option value="offline">Offline</option>
                        <option value="blocked">Blocked</option>
                    </select>

                    <select id="user-sort" class="form-select" style="flex: 1; min-width: 150px;" onchange="fetchUsers()">
                        <option value="last_seen">Recently Active</option>
                        <option value="created_at">Registration Date</option>
                        <option value="balance">Account Balance</option>
                        <option value="total_spent">Total Spent</option>
                        <option value="total_orders">Total Orders</option>
                        <option value="last_deposit_at">Last Deposit</option>
                    </select>

                    <select id="user-dir" class="form-select" style="flex: 0.5; min-width: 100px;" onchange="fetchUsers()">
                        <option value="DESC">Descending</option>
                        <option value="ASC">Ascending</option>
                    </select>

                    <button class="btn btn-primary" onclick="fetchUsers()">Search</button>
                </div>
                
                <!-- New Responsive Table for Users -->
                <div id="users-grid" class="mt-4">
                    <div class="loading">Loading user network...</div>
                </div>
            </div>
        </div>

        <!-- HOLIDAYS TAB -->
        <div id="tab-holidays" class="tab-content">
            <div class="card">
                <div class="card-header">Add New Holiday / Event</div>
                <div class="stats-grid" style="align-items:end;">
                    <div class="form-group mb-0">
                        <label class="form-label">Name</label>
                        <input type="text" id="hol-name" class="form-input" placeholder="e.g. Christmas">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Dates</label>
                        <div style="display:flex; gap:4px;">
                            <input type="date" id="hol-start" class="form-input">
                            <input type="date" id="hol-end" class="form-input">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Discount %</label>
                        <input type="number" id="hol-percent" class="form-input" placeholder="10">
                    </div>
                    <button class="btn btn-primary" onclick="addHoliday()">Add Event</button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Upcoming Events</div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Discount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="holidays-list"></tbody>
                    </table>
                </div>
            </div>
            
            <!-- Holiday Reference Calendar -->
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <span>📅 Holiday Reference Calendar (Upcoming)</span>
                    <select id="holiday-filter" class="form-select" style="max-width: 200px;" onchange="filterHolidayCalendar()">
                        <option value="all">All Holidays</option>
                        <option value="ethiopian">Ethiopian</option>
                        <option value="international">International</option>
                        <option value="islamic">Islamic</option>
                        <option value="christian">Christian</option>
                        <option value="shopping">Shopping/Sales</option>
                    </select>
                </div>
                <div style="max-height: 500px; overflow-y: auto;">
                    <div id="holiday-calendar-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; padding: 16px;">
                        <!-- Populated by JS -->
                    </div>
                </div>
                <div style="padding: 12px; background: var(--bg-secondary); border-top: 1px solid var(--border-color); font-size: 11px; color: var(--text-secondary);">
                    💡 Click on any holiday to auto-fill the form above with suggested dates and create a discount campaign.
                </div>
            </div>
        </div>

        <!-- SERVICES TAB -->
        <div id="tab-services" class="tab-content">
            <div class="card">
                <div class="card-header">Manage Recommended Services</div>
                <div class="form-group">
                    <label class="form-label">Bulk Update IDs (Comma Separated)</label>
                    <div class="flex gap-2">
                        <input type="text" id="bulk-ids" class="form-input" placeholder="e.g. 22, 34, 55">
                        <button class="btn btn-primary" onclick="bulkUpdateServices()">Sync List</button>
                    </div>
                    <p style="color:var(--text-secondary); font-size:12px; margin-top:8px;">Replacing current list with these IDs.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <span>Service Search & Management</span>
                    <div class="flex gap-2">
                        <button class="btn btn-sm btn-secondary filter-btn active" data-filter="all" onclick="setServiceFilter('all')">All</button>
                        <button class="btn btn-sm btn-secondary filter-btn" data-filter="recommended" onclick="setServiceFilter('recommended')">Recommended</button>
                        <button class="btn btn-sm btn-secondary filter-btn" data-filter="hidden" onclick="setServiceFilter('hidden')">Hidden</button>
                    </div>
                </div>
                <input type="text" id="service-search" class="form-input mb-4" placeholder="Search service by name or ID...">
                <div id="services-results" style="display: grid; gap: 8px; max-height: 400px; overflow-y: auto;"></div>
            </div>
        </div>

        <!-- ALERTS TAB -->
        <div id="tab-alerts" class="tab-content">
            <div class="card">
                <div class="card-header">User Alerts Manager</div>
                <div class="stats-grid">
                    <div>
                        <div class="form-group">
                            <label class="form-label">User ID (Telegram ID)</label>
                            <input type="number" id="alert-user-id" class="form-input" placeholder="e.g. 111">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Message</label>
                            <textarea id="alert-message" class="form-textarea" rows="3" placeholder="Enter alert message..."></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button class="btn btn-primary" onclick="sendAlert()">Send Alert</button>
                            <button class="btn btn-secondary" onclick="loadUserAlerts()">View History</button>
                        </div>
                    </div>
                </div>
                
                <div id="alert-history" class="mt-4" style="display: none;">
                    <h3 class="form-label">Message History</h3>
                    <div id="alert-list" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                        <!-- Alerts populated -->
                    </div>
                </div>
            </div>
        </div>

        <!-- CHAT TAB -->
        <div id="tab-chat" class="tab-content">
            <div class="card p-0 overflow-hidden">
                <div class="chat-wrapper">
                    <!-- Users Sidebar -->
                    <div id="chat-sidebar" class="chat-sidebar">
                        <div class="chat-sidebar-header">
                            <span>💬 Conversations</span>
                            <button class="btn btn-secondary btn-sm" onclick="loadChatUsers()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                            </button>
                        </div>
                        <div id="chat-users-list" class="chat-users-list">
                            <div class="loading">Loading chats...</div>
                        </div>
                    </div>
                    
                    <!-- Chat View -->
                    <div id="chat-main" class="chat-main">
                        <!-- No Selection State -->
                        <div id="chat-no-selection" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); padding: 40px; text-align: center;">
                            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-4">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.3;">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                            <h3 style="color: white; font-weight: 600; margin-bottom: 8px;">Support Center</h3>
                            <p style="font-size: 13px; max-width: 200px;">Select a customer from the list to start assisting them.</p>
                        </div>
                        
                        <!-- Active Chat View -->
                        <div id="chat-active-panel" style="display: none; flex-direction: column; height: 100%;">
                            <div class="chat-main-header">
                                <div class="back-to-list" onclick="backToChatList()">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                                </div>
                                <img id="chat-user-avatar" src="" alt="Avatar" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.1);">
                                <div style="flex: 1; min-width: 0;">
                                    <div id="chat-user-name" style="font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></div>
                                    <div id="chat-user-id" style="font-size: 10px; color: var(--text-muted);"></div>
                                </div>
                                <button class="btn btn-secondary btn-sm" onclick="loadAdminChatMessages()" title="Refresh messages">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                                </button>
                                <button class="btn btn-danger btn-sm ml-2" onclick="closeAdminChat()" title="Close chat & clear history">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    Close Chat
                                </button>
                            </div>
                            
                            <div id="admin-chat-messages" class="chat-messages-container">
                                <!-- Messages injected -->
                            </div>
                            
                            <div class="chat-input-wrapper">
                                <input type="text" id="admin-chat-input" class="form-input" placeholder="Type a message..." style="border-radius: 20px; padding-left: 18px;">
                                <button class="btn btn-primary" onclick="sendAdminReply()" style="border-radius: 50%; width: 42px; height: 42px; padding: 0;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transform: rotate(45deg) translate(-1px, 1px);"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SETTINGS TAB -->
        <div id="tab-settings" class="tab-content">
            <!-- EMERGENCY CONTROL -->
            <div class="card" style="border: 1px solid rgba(255, 0, 0, 0.3); background: rgba(255, 0, 0, 0.05); margin-bottom: 2rem;">
                <div class="card-header" style="color: #ff4d4d; display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
                    Emergency System Control
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-bold text-white text-sm">Maintenance mode</div>
                            <div class="text-[10px] text-gray-500">When active, users cannot place orders. Only allowed IDs can access.</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="maintenance-mode-toggle" onchange="updateMaintenance()">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Allowed User IDs (White-list)</label>
                        <input type="text" id="maintenance-allowed-ids" class="form-input" placeholder="e.g. 111, 222, 333" onblur="updateMaintenance()">
                        <p class="text-[9px] text-gray-500 mt-1">Separate multiple IDs with commas.</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Global Marquee Settings</div>
                <div class="form-group">
                    <label class="flex items-center gap-2" style="cursor: pointer;">
                        <input type="checkbox" id="marquee-enabled" style="width: 16px; height: 16px;">
                        <span>Enable Marquee on Homepage</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Marquee Text</label>
                    <input type="text" id="marquee-text" class="form-input" placeholder="Enter announcement text...">
                </div>
                <button class="btn btn-primary" onclick="saveMarquee()">Save Settings</button>
            </div>
            
            <div class="card">
                <div class="card-header">Currency & Rate Settings</div>
                <div class="form-group">
                    <label class="form-label">Rate Multiplier (USD to ETB)</label>
                    <input type="number" id="rate-multiplier" class="form-input" placeholder="400" step="0.01" min="1">
                    <p style="color:var(--text-secondary); font-size:12px; margin-top:8px;">All service rates will be multiplied by this value and displayed in ETB. Default: 400</p>
                </div>
                <button class="btn btn-primary" onclick="saveRateMultiplier()">Save Rate Multiplier</button>
            </div>
        </div>

        <!-- ANALYTICS TAB -->
        <div id="tab-analytics" class="tab-content">
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">⚡ Quick Actions</div>
                <div class="stats-grid" style="margin-bottom:0;">
                    <button class="btn btn-secondary" onclick="exportOrders()" style="width:100%; justify-content:center; padding:16px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export Orders (CSV)
                    </button>
                    <button class="btn btn-secondary" onclick="exportUsers()" style="width:100%; justify-content:center; padding:16px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Export Users (CSV)
                    </button>
                    <button class="btn btn-secondary" onclick="refreshAllServices()" style="width:100%; justify-content:center; padding:16px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                        Sync Services
                    </button>
                    <button class="btn btn-secondary" onclick="clearOrderCache()" style="width:100%; justify-content:center; padding:16px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        Clear Cache
                    </button>
                </div>
            </div>

            <!-- System Health -->
            <div class="card">
                <div class="card-header">🔧 System Health</div>
                <div id="system-health" class="stats-grid" style="margin-bottom:0;">
                    <div class="stat-card">
                        <div class="stat-label">Services Cached</div>
                        <div class="stat-value" id="health-services">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Users (24h)</div>
                        <div class="stat-value success" id="health-active-users">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">API Status</div>
                        <div class="stat-value success" id="health-api">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Database Size</div>
                        <div class="stat-value" id="health-db">-</div>
                    </div>
                </div>
            </div>

            <!-- Top Customers -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="card mb-0">
                    <div class="card-header">🏆 Top Customers (by Spend)</div>
                    <div id="top-customers-list" class="table-container" style="max-height: 300px; overflow-y: auto;">
                        <div class="loading">Loading top customers</div>
                    </div>
                </div>
                
                <div class="card mb-0">
                    <div class="card-header">🔥 Popular Services</div>
                    <div id="popular-services-list" class="table-container" style="max-height: 300px; overflow-y: auto;">
                        <div class="loading">Loading popular services</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOT TAB -->
        <div id="tab-bot" class="tab-content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Bot Status Card -->
                <div class="card">
                    <div class="card-header flex justify-between items-center">
                        <span>🤖 Bot Status</span>
                        <button class="btn btn-secondary btn-sm" onclick="loadBotData()">Refresh</button>
                    </div>
                    <div id="bot-status-content" class="p-4">
                        <div class="loading">Fetching bot info...</div>
                    </div>
                    <div class="p-4 border-t border-white/5">
                        <div class="form-group mb-4">
                            <label class="form-label">Webhook URL (HTTPS Required)</label>
                            <input type="text" id="webhook-url-input" class="form-input" placeholder="https://yoursite.com/webhook_handler.php">
                            <p class="text-xs text-secondary mt-1">Leave empty to auto-detect.</p>
                        </div>
                        <div class="bg-accent/10 border border-accent/20 rounded p-3 mb-4">
                            <p class="text-xs text-accent">
                                💡 <b>Local Testing:</b> Telegram webhooks require <b>HTTPS</b>. Use a tool like <a href="https://ngrok.com" target="_blank" class="underline">ngrok</a> to expose your localhost to the internet.
                            </p>
                        </div>
                        <div class="flex gap-2">
                             <button class="btn btn-primary btn-sm flex-1 justify-center" onclick="setWebhook()">Set Webhook</button>
                             <button class="btn btn-danger btn-sm" onclick="deleteWebhook()">Delete</button>
                             <button class="btn btn-secondary btn-sm" onclick="deleteAllBotMessages()" title="Retract all messages from history">🗑️ Clear Logs</button>
                         </div>
                    </div>
                </div>

                <!-- Broadcast Card -->
                <div class="card">
                    <div class="card-header">📢 Global Broadcast</div>
                    <div class="p-4">
                        <div class="form-group mb-4">
                            <div class="flex justify-between items-center mb-1">
                                <label class="form-label">Message / Caption (HTML Support)</label>
                                <div class="text-[10px] text-accent font-mono">
                                    {first_name} {balance} {id}
                                </div>
                            </div>
                            <textarea id="broadcast-message" class="form-textarea" rows="4" placeholder="Hi {first_name}, your balance is {balance} ETB..."></textarea>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Target Audience</label>
                            <select id="broadcast-audience" class="form-input text-xs">
                                <option value="all">Every User (Global)</option>
                                <option value="active">Active Recently (7 days)</option>
                                <option value="with_balance">Paid Users (Balance > 0)</option>
                                <option value="blocked">Blocked Users ONLY</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label class="form-label">Image URL (Optional)</label>
                            <input type="text" id="broadcast-image-url" class="form-input" placeholder="https://example.com/image.jpg" oninput="updateImagePreview()">
                            <div id="broadcast-image-preview" class="mt-2 hidden">
                                <img src="" alt="Preview" class="w-full max-h-40 object-cover rounded border border-white/10">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mb-4">
                            <div class="form-group">
                                <label class="form-label text-xs">Button Text</label>
                                <input type="text" id="broadcast-btn-text" class="form-input text-xs" value="Start App">
                            </div>
                            <div class="form-group">
                                <label class="form-label text-xs">Button URL</label>
                                <input type="text" id="broadcast-btn-url" class="form-input text-xs" value="https://paxyo.com/smm.php">
                            </div>
                        </div>

                        <button class="btn btn-primary w-full justify-center" onclick="sendBroadcast()" id="btn-send-broadcast">
                            🚀 Send Rich Broadcast
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Broadcast History -->
            <div class="card mt-6">
                <div class="card-header flex justify-between items-center">
                    <span>📜 Recent Broadcasts (CRUD)</span>
                    <button class="btn btn-secondary btn-sm" onclick="fetchBroadcastHistory()">Refresh</button>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Preview</th>
                                <th>Reach</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bot-history-list">
                            <tr><td colspan="4" class="text-center p-4 text-secondary">Loading history...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bot Webhook Info -->
            <div class="card mt-6">
                <div class="card-header">🌐 Webhook Details</div>
                <div id="webhook-info-content" class="p-4 font-mono text-xs overflow-x-auto whitespace-pre-wrap">
                    <div class="text-secondary">Loading webhook info...</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- Direct Message Card -->
                <div class="card">
                    <div class="card-header">✉️ Direct Message</div>
                    <div class="p-4">
                        <div class="form-group mb-4 relative">
                            <label class="form-label">Search User or Enter ID</label>
                            <div class="flex gap-2">
                                <input type="number" id="direct-tg-id" class="form-input flex-1" placeholder="Telegram ID">
                                <input type="text" id="direct-user-search" class="form-input flex-1" placeholder="Search by name..." oninput="searchDirectUsers(this.value)">
                            </div>
                            <div id="direct-search-results" class="absolute left-0 right-0 top-full bg-[#1e1e2a] border border-white/10 rounded-b mt-1 hidden z-50 max-h-40 overflow-y-auto">
                                <!-- Results here -->
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label">Message ({first_name} supported)</label>
                            <textarea id="direct-message-text" class="form-textarea" rows="3" placeholder="Hi {first_name}, ..."></textarea>
                        </div>
                        <button class="btn btn-primary w-full justify-center" onclick="sendDirectMessage()">
                            📤 Send Private Message
                        </button>
                    </div>
                </div>

                <!-- Auto-Reminder (Retention) Card -->
                <div class="card">
                    <div class="card-header flex justify-between items-center">
                        <span>⏰ User Retention / Reminders</span>
                        <button class="btn btn-primary btn-sm" onclick="openReminderModal()">+ Add Rule</button>
                    </div>
                    <div class="p-4">
                        <p class="text-[11px] text-secondary mb-3">These rules send automatic messages to users who haven't deposited after a certain time.</p>
                        <div id="reminder-rules-list" class="space-y-2">
                            <!-- Rules populated here -->
                            <div class="text-center p-4 text-secondary text-xs">Loading rules...</div>
                        </div>
                        <button class="btn btn-secondary w-full justify-center mt-4 text-xs" onclick="processRemindersNow()">
                            ⚡ Process All Reminders Now
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bot Advanced Settings -->
            <div class="card mt-6">
                <div class="card-header">⚙️ Bot General Configuration</div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="form-group mb-4">
                            <label class="form-label">Welcome Message (New Users)</label>
                            <textarea id="setting-welcome-message" class="form-textarea" rows="3" placeholder="Welcome {first_name}!"></textarea>
                        </div>
                        <div class="form-group mb-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="setting-maintenance-mode" class="w-4 h-4">
                                <span class="font-bold text-sm">Bot Maintenance Mode</span>
                            </label>
                            <p class="text-[10px] text-secondary mt-1">If enabled, users will see the maintenance message below instead of the app.</p>
                        </div>
                    </div>
                    <div>
                        <div class="form-group mb-4">
                            <label class="form-label">Maintenance Message</label>
                            <textarea id="setting-maintenance-message" class="form-textarea" rows="3" placeholder="We are updating..."></textarea>
                        </div>
                        <button class="btn btn-primary w-full justify-center" onclick="saveBotSettings()">
                            💾 Save Bot Configuration
                        </button>
                    </div>
                </div>
            </div>

            <!-- Auto-Reply Keywords -->
            <div class="card mt-6">
                <div class="card-header flex justify-between items-center">
                    <span>💬 Auto-Reply Keywords</span>
                    <button class="btn btn-primary btn-sm" onclick="addAutoReply()">+ Add Keyword</button>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Keyword</th>
                                <th>Response</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="auto-replies-list">
                            <tr><td colspan="3" class="text-center p-4 text-secondary">Loading auto-replies...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- SCRIPTS -->
    <script>
        // Init
        let allServices = [];
        let recommendedIds = new Set();
        let hiddenServiceIds = new Set();
        let currentServiceFilter = 'all';
        let loaded = false;

        // --- AUTH & TAB LOGIC ---
        function loadTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            
            // Toggle body class for chat to hide AI bubble
            document.body.classList.toggle('tab-chat-active', tab === 'chat');
            
            const tabContent = document.getElementById('tab-' + tab);
            if (tabContent) tabContent.style.display = 'block';
            
            if (tab === 'dashboard') loadStats();
            if (tab === 'orders') fetchOrders();
            if (tab === 'deposits') fetchDeposits();
            if (tab === 'users') fetchUsers();
            if (tab === 'services') renderServices(document.getElementById('service-search').value);
            if (tab === 'holidays') fetchHolidays();
            if (tab === 'alerts') loadUserAlerts();
            if (tab === 'chat') loadChatUsers();
            if (tab === 'analytics') loadAnalytics();
            if (tab === 'bot') {
                loadBotData();
                fetchBroadcastHistory();
                fetchReminderRules();
                fetchBotSettings();
                fetchAutoReplies();
            }
            if (tab === 'settings') loadSettings();
        }

        // --- BOT MANAGEMENT ---
        async function loadBotData() {
            const statusDiv = document.getElementById('bot-status-content');
            const webhookDiv = document.getElementById('webhook-info-content');
            
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_bot_status' })
                });
                const data = await res.json();
                
                if (data.success) {
                    const bot = data.bot;
                    statusDiv.innerHTML = `
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-accent/20 rounded-full flex items-center justify-center text-2xl">🤖</div>
                            <div>
                                <div class="font-bold text-lg">${bot ? bot.first_name : 'N/A'}</div>
                                <div class="text-secondary">@${bot ? bot.username : 'unknown'}</div>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between border-b border-white/5 pb-1">
                                <span class="text-secondary">Bot ID:</span>
                                <span class="font-mono">${bot ? bot.id : 'N/A'}</span>
                            </div>
                            <div class="flex justify-between border-b border-white/5 pb-1">
                                <span class="text-secondary">Webhook Status:</span>
                                <span class="${data.webhook && data.webhook.url ? 'text-success' : 'text-danger'} font-bold">
                                    ${data.webhook && data.webhook.url ? 'SET' : 'NOT SET'}
                                </span>
                            </div>
                            <div class="flex justify-between border-b border-white/5 pb-1">
                                <span class="text-secondary">Total Users:</span>
                                <span class="font-bold">${data.user_count}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 mt-4 pt-4 border-t border-white/5">
                                <div class="text-center">
                                    <div class="text-[10px] text-secondary uppercase">Joined</div>
                                    <div class="font-bold text-accent">+${data.stats.joined_today}</div>
                                </div>
                                <div class="text-center border-l border-white/10">
                                    <div class="text-[10px] text-secondary uppercase">Active</div>
                                    <div class="font-bold text-success">${data.stats.active_24h}</div>
                                </div>
                                <div class="text-center border-l border-white/10">
                                    <div class="text-[10px] text-secondary uppercase">Paid</div>
                                    <div class="font-bold text-warning">${data.stats.with_balance}</div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    webhookDiv.textContent = JSON.stringify(data.webhook, null, 2);
                } else {
                    statusDiv.innerHTML = `<div class="text-danger">Error: ${data.error}</div>`;
                }
            } catch (e) {
                console.error(e);
                statusDiv.innerHTML = `<div class="text-danger">Failed to fetch bot data.</div>`;
            }
        }

        async function setWebhook() {
            const webhookUrl = document.getElementById('webhook-url-input').value;
            if (!confirm("Set webhook? Telegram will send messages to this URL.")) return;
            
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'set_webhook', webhook_url: webhookUrl })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                } else {
                    alert("Error: " + data.error);
                }
                loadBotData();
            } catch (e) { console.error(e); }
        }

        async function deleteWebhook() {
            if (!confirm("Remove webhook? Bot will stop receiving messages.")) return;
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_webhook' })
                });
                const data = await res.json();
                alert(data.message || data.error);
                loadBotData();
            } catch (e) { console.error(e); }
        }

        async function sendBroadcast() {
            const msg = document.getElementById('broadcast-message').value;
            const imageUrl = document.getElementById('broadcast-image-url').value;
            const btnText = document.getElementById('broadcast-btn-text').value;
            const btnUrl = document.getElementById('broadcast-btn-url').value;
            
            if (!msg && !imageUrl) return alert("Please enter at least a message or an image URL.");
            if (!confirm(`Send this broadcast to ALL users?`)) return;
            
            const btn = document.getElementById('btn-send-broadcast');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = "⏳ Sending... Please wait";
            
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'broadcast', 
                        message: msg,
                        image_url: imageUrl,
                        button_text: btnText,
                        button_url: btnUrl,
                        audience: document.getElementById('broadcast-audience').value
                    })
                });
                const data = await res.json();
                alert(data.message || data.error);
                if (data.success) {
                    document.getElementById('broadcast-message').value = '';
                    document.getElementById('broadcast-image-url').value = '';
                    document.getElementById('broadcast-btn-text').value = '';
                    document.getElementById('broadcast-btn-url').value = '';
                    updateImagePreview();
                }
            } catch (e) { 
                console.error(e); 
                alert("Broadcast failed.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        async function fetchBroadcastHistory() {
            const list = document.getElementById('bot-history-list');
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_broadcasts' })
                });
                const data = await res.json();
                
                if (data.success && data.broadcasts.length > 0) {
                    list.innerHTML = data.broadcasts.map(b => `
                        <tr>
                            <td class="text-xs text-secondary">${new Date(b.created_at).toLocaleString()}</td>
                            <td class="text-xs">
                                <div class="truncate max-w-[200px]" title="${b.message.replace(/"/g, '&quot;')}">${b.message.substring(0, 50)}${b.message.length > 50 ? '...' : ''}</div>
                                ${b.image_url ? '<span class="text-[10px] text-accent">🖼️ Image</span>' : ''}
                            </td>
                            <td class="text-xs font-bold text-success">${b.sent_count}</td>
                            <td class="flex gap-2">
                                <button onclick="viewBroadcastReport(${b.id})" class="btn btn-sm btn-primary" style="padding: 2px 8px; font-size: 10px;">📊 Report</button>
                                <button onclick="editBroadcast(${b.id}, \`${b.message.replace(/`/g, '\\`').replace(/\n/g, '\\n')}\`)" class="btn btn-sm btn-secondary" style="padding: 2px 8px; font-size: 10px;">✏️ Edit</button>
                                <button onclick="retractBroadcast(${b.id})" class="btn btn-sm btn-danger" style="padding: 2px 8px; font-size: 10px;">🗑️ Retract</button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    list.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-secondary">No broadcast history found.</td></tr>';
                }
            } catch (e) {
                list.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-danger">Failed to load history.</td></tr>';
            }
        }

        async function retractBroadcast(id) {
            if (!confirm("Retract this broadcast? This will attempt to delete the message for ALL users who received it.")) return;
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_broadcast_messages', broadcast_id: id })
                });
                const data = await res.json();
                alert(data.message || data.error);
                fetchBroadcastHistory();
            } catch (e) { console.error(e); }
        }

        async function editBroadcast(id, currentMsg) {
            const newMsg = prompt("Enter new message (will update for all users):", currentMsg);
            if (newMsg === null || newMsg === currentMsg) return;
            
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'edit_broadcast_messages', broadcast_id: id, message: newMsg })
                });
                const data = await res.json();
                alert(data.message || data.error);
                fetchBroadcastHistory();
            } catch (e) { console.error(e); }
        }

        // --- DIRECT MESSAGE ---
        let directSearchTimeout;
        async function searchDirectUsers(query) {
            clearTimeout(directSearchTimeout);
            const resultsDiv = document.getElementById('direct-search-results');
            if (query.length < 2) {
                resultsDiv.classList.add('hidden');
                return;
            }
            
            directSearchTimeout = setTimeout(async () => {
                try {
                    const res = await fetch('api_bot.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'search_users', query: query })
                    });
                    const data = await res.json();
                    if (data.success && data.users.length > 0) {
                        resultsDiv.innerHTML = data.users.map(u => `
                            <div class="p-2 border-b border-white/5 hover:bg-white/5 cursor-pointer flex justify-between items-center" onclick="selectDirectUser('${u.tg_id}', '${u.first_name}')">
                                <span class="text-xs font-bold">${u.first_name}</span>
                                <span class="text-[10px] text-secondary">ID: ${u.tg_id}</span>
                            </div>
                        `).join('');
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.innerHTML = '<div class="p-2 text-xs text-secondary">No users found</div>';
                        resultsDiv.classList.remove('hidden');
                    }
                } catch (e) { console.error(e); }
            }, 300);
        }

        function selectDirectUser(id, name) {
            document.getElementById('direct-tg-id').value = id;
            document.getElementById('direct-user-search').value = name;
            document.getElementById('direct-search-results').classList.add('hidden');
        }

        async function viewBroadcastReport(id) {
            const modal = document.getElementById('broadcast-report-modal');
            const content = document.getElementById('broadcast-report-content');
            modal.classList.add('active');
            content.innerHTML = '<div class="p-10 text-center">Loading Report...</div>';

            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_broadcast_report', broadcast_id: id })
                });
                const data = await res.json();
                if (data.success) {
                    const successUsers = data.report.filter(l => l.status === 'success');
                    const failedUsers = data.report.filter(l => l.status === 'failed');

                    let html = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 h-[400px]">
                            <div class="border border-white/10 rounded bg-white/5 flex flex-col">
                                <div class="bg-success/20 p-2 text-success text-xs font-bold border-b border-white/10 flex justify-between">
                                    <span>✅ DELIVERED</span>
                                    <span>${successUsers.length}</span>
                                </div>
                                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                                    ${successUsers.map(u => `
                                        <div class="flex justify-between items-center p-1 border-b border-white/5 text-[10px]">
                                            <span>${u.first_name || 'User'} (@${u.username || '?'})</span>
                                            <button onclick="closeReportModal(); openBotMessage('${u.tg_id}')" class="text-accent hover:underline">Message</button>
                                        </div>
                                    `).join('') || '<div class="text-center text-secondary py-10">No deliveries</div>'}
                                </div>
                            </div>
                            <div class="border border-white/10 rounded bg-white/5 flex flex-col">
                                <div class="bg-danger/20 p-2 text-danger text-xs font-bold border-b border-white/10 flex justify-between">
                                    <span>❌ FAILED</span>
                                    <span>${failedUsers.length}</span>
                                </div>
                                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                                    ${failedUsers.map(u => `
                                        <div class="p-1 border-b border-white/5 text-[10px]">
                                            <div class="font-bold">${u.first_name || 'User'}</div>
                                            <div class="text-danger text-[9px] italic">${u.error_msg}</div>
                                        </div>
                                    `).join('') || '<div class="text-center text-secondary py-10">No failures</div>'}
                                </div>
                            </div>
                        </div>
                    `;
                    content.innerHTML = html;
                }
            } catch (e) {
                content.innerHTML = '<div class="p-10 text-center text-danger">Failed to load analytics.</div>';
            }
        }

        function closeReportModal() {
            document.getElementById('broadcast-report-modal').classList.remove('active');
        }

        async function sendDirectMessage() {
            const tgId = document.getElementById('direct-tg-id').value;
            const msg = document.getElementById('direct-message-text').value;
            
            if (!tgId || !msg) return alert("Enter ID and Message");
            
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'direct_message', tg_id: tgId, message: msg })
                });
                const data = await res.json();
                alert(data.message || data.error);
                if (data.success) {
                    document.getElementById('direct-message-text').value = '';
                }
            } catch (e) { console.error(e); }
        }

        // --- AUTO-REMINDERS ---
        async function fetchReminderRules() {
            const list = document.getElementById('reminder-rules-list');
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_reminder_rules' })
                });
                const data = await res.json();
                if (data.success) {
                    if (data.rules.length === 0) {
                        list.innerHTML = '<div class="text-center p-4 text-secondary text-xs">No active reminder rules.</div>';
                        return;
                    }
                    list.innerHTML = data.rules.map(r => `
                        <div class="bg-white/5 border border-white/10 rounded p-3 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                ${r.image_url ? `<img src="${r.image_url}" class="w-8 h-8 rounded object-cover border border-white/10">` : '<div class="w-8 h-8 rounded bg-white/5 flex items-center justify-center text-[10px]">TXT</div>'}
                                <div>
                                    <div class="font-bold text-xs">${r.name}</div>
                                    <div class="text-[10px] text-accent">Wait: ${r.trigger_hours}h</div>
                                    <div class="text-[10px] text-secondary truncate max-w-[150px]">${r.message}</div>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button onclick="deleteReminderRule(${r.id})" class="btn btn-sm btn-danger px-2">🗑️</button>
                                <button onclick="toggleReminderRule(${r.id}, ${r.is_active ? 0 : 1})" class="btn btn-sm ${r.is_active ? 'btn-success' : 'btn-secondary'} px-2">
                                    ${r.is_active ? 'ON' : 'OFF'}
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (e) { console.error(e); }
        }

        function openReminderModal() {
            const name = prompt("Rule Name (e.g. 24h Reminder):");
            if (!name) return;
            const hours = prompt("Hours after joining to send (e.g. 24):");
            if (!hours) return;
            const msg = prompt("Message to send ({first_name} supported):");
            if (!msg) return;
            const imageUrl = prompt("Image URL (Optional):");
            const btnText = prompt("Button Text (Optional):", "Start App");
            const btnUrl = prompt("Button URL (Optional):", "https://paxyo.com/smm.php");

            saveReminderRule({
                name: name,
                hours: hours,
                message: msg,
                image_url: imageUrl,
                button_text: btnText,
                button_url: btnUrl
            });
        }

        async function saveReminderRule(data) {
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_reminder_rule', ...data })
                });
                const result = await res.json();
                alert(result.message || result.error);
                fetchReminderRules();
            } catch (e) { console.error(e); }
        }

        async function deleteReminderRule(id) {
            if (!confirm("Delete this rule?")) return;
            try {
                await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_reminder_rule', id: id })
                });
                fetchReminderRules();
            } catch (e) { console.error(e); }
        }

        async function toggleReminderRule(id, active) {
            try {
                await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_reminder_rule', id: id, active: active })
                });
                fetchReminderRules();
            } catch (e) { console.error(e); }
        }

        async function processRemindersNow() {
            const btn = event.target;
            const original = btn.innerHTML;
            btn.innerHTML = "⏳ Processing Batch...";
            btn.disabled = true;

            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'process_reminders' })
                });
                const data = await res.json();
                alert(data.message);
            } catch (e) { console.error(e); }
            finally {
                btn.innerHTML = original;
                btn.disabled = false;
            }
        }

        async function deleteAllBotMessages() {
            if (!confirm("CRITICAL: Delete all tracked bot messages from history? This will attempt to delete the last 500 messages sent across all users.")) return;
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_all_messages' })
                });
                const data = await res.json();
                alert(data.message || data.error);
                fetchBroadcastHistory();
            } catch (e) { console.error(e); }
        }

        function updateImagePreview() {
            const url = document.getElementById('broadcast-image-url').value;
            const previewDiv = document.getElementById('broadcast-image-preview');
            const img = previewDiv.querySelector('img');
            
            if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
                img.src = url;
                previewDiv.classList.remove('hidden');
                img.onerror = () => previewDiv.classList.add('hidden');
            } else {
                previewDiv.classList.add('hidden');
            }
        }

        // --- BOT ADVANCED SETTINGS ---
        async function fetchBotSettings() {
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_bot_settings' })
                });
                const data = await res.json();
                if (data.success && data.settings) {
                    document.getElementById('setting-welcome-message').value = data.settings.welcome_message || '';
                    document.getElementById('setting-maintenance-message').value = data.settings.maintenance_message || '';
                    document.getElementById('setting-maintenance-mode').checked = (data.settings.maintenance_mode === '1');
                }
            } catch (e) { console.error(e); }
        }

        async function saveBotSettings() {
            const welcome = document.getElementById('setting-welcome-message').value;
            const maintMsg = document.getElementById('setting-maintenance-message').value;
            const maintMode = document.getElementById('setting-maintenance-mode').checked ? '1' : '0';

            const settings = {
                welcome_message: welcome,
                maintenance_message: maintMsg,
                maintenance_mode: maintMode
            };

            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_bot_settings', settings: settings })
                });
                const data = await res.json();
                alert(data.message || data.error);
            } catch (e) { console.error(e); }
        }

        // --- AUTO REPLIES ---
        async function fetchAutoReplies() {
            const list = document.getElementById('auto-replies-list');
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_auto_replies' })
                });
                const data = await res.json();
                if (data.success) {
                    if (data.replies.length === 0) {
                        list.innerHTML = '<tr><td colspan="3" class="text-center p-4 text-secondary">No auto-replies defined.</td></tr>';
                        return;
                    }
                    list.innerHTML = data.replies.map(r => `
                        <tr>
                            <td class="font-bold text-accent">${r.keyword}</td>
                            <td class="text-xs text-secondary truncate max-w-[200px]">${r.reply}</td>
                            <td>
                                <div class="flex gap-1">
                                    <button onclick="editAutoReply(${r.id}, '${r.keyword.replace(/'/g, "\\'")}', '${r.reply.replace(/'/g, "\\'")}')" class="btn btn-sm btn-secondary px-2">Edit</button>
                                    <button onclick="deleteAutoReply(${r.id})" class="btn btn-sm btn-danger px-2">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (e) { console.error(e); }
        }

        function addAutoReply() {
            const keyword = prompt("Enter keyword to match (e.g. 'refund'):");
            if (!keyword) return;
            const reply = prompt("Enter the bot response:");
            if (!reply) return;
            saveAutoReply({ keyword, reply });
        }

        function editAutoReply(id, keyword, reply) {
            const newKeyword = prompt("Edit keyword:", keyword);
            if (!newKeyword) return;
            const newReply = prompt("Edit response:", reply);
            if (!newReply) return;
            saveAutoReply({ id, keyword: newKeyword, reply: newReply });
        }

        async function saveAutoReply(data) {
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_auto_reply', ...data })
                });
                const result = await res.json();
                alert(result.message || result.error);
                fetchAutoReplies();
            } catch (e) { console.error(e); }
        }

        async function deleteAutoReply(id) {
            if (!confirm("Delete this auto-reply?")) return;
            try {
                await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_auto_reply', id: id })
                });
                fetchAutoReplies();
            } catch (e) { console.error(e); }
        }

        // --- GLOBAL HEARTBEAT (10s) ---
        let heartbeatInterval = null;
        function startAdminHeartbeat() {
            if (heartbeatInterval) clearInterval(heartbeatInterval);
            
            async function updateHeartbeat() {
                try {
                    const res = await fetch('api_heartbeat.php');
                    const data = await res.json();
                    if (data.success) {
                        // Update online users count in ALL headers where it might exist
                        const headers = document.querySelectorAll('.card-header span, .admin-subtitle');
                        headers.forEach(h => {
                            if (h.innerHTML.includes('online')) {
                                h.innerHTML = h.innerHTML.replace(/●.*online/i, `● ${data.online_users} online`);
                            }
                        });
                        
                        // Update specific User Management badge if active
                        const badge = document.getElementById('user-count-header');
                        if (badge) badge.textContent = `${data.total_users} users / ${data.online_users} online`;
                        
                        // Smoothly update dashboard stats if they are visible
                        const dashPending = document.getElementById('dash-pending-orders');
                        if (dashPending && data.pending_orders > 0) {
                            dashPending.textContent = data.pending_orders;
                            dashPending.classList.add('pulse');
                        }
                    }
                } catch (e) { console.error("Heartbeat error:", e); }
            }
            
            updateHeartbeat();
            heartbeatInterval = setInterval(updateHeartbeat, 4000); // 4s for aggressive real-time
        }

        // Utils
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadTab('dashboard');
            startAdminHeartbeat(); // Start real-time status tracker
            loadGeneralData();
            loadStats();
            
            // Preload services with robust error handling
            fetch('../get_service.php')
                .then(r => {
                    if (!r.ok) throw new Error('Network response was not ok: ' + r.statusText);
                    return r.json();
                })
                .then(data => { 
                    if (Array.isArray(data)) {
                        allServices = data;
                        console.log('Services loaded successfully:', allServices.length);
                    } else {
                        console.error('Services data is not an array:', data);
                    }
                })
                .catch(err => {
                    console.error('Failed to load services:', err);
                    // Fallback try absolute path if relative failed
                    fetch('/paxyo/get_service.php')
                        .then(r => r.json())
                        .then(data => {
                             if(Array.isArray(data)) allServices = data;
                        }).catch(e => console.log('Fallback failed', e));
                });
        });

        async function loadGeneralData() {
            // Fetch recommended
            try {
                const res = await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'get_recommended' })
                });
                const data = await res.json();
                if (data.success) {
                    recommendedIds = new Set(data.ids);
                    document.getElementById('bulk-ids').value = data.ids.join(', ');
                }
            } catch (e) { console.error(e); }

            // Fetch hidden services
            try {
                const res = await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'get_hidden' })
                });
                const data = await res.json();
                if (data.success) {
                    hiddenServiceIds = new Set(data.ids);
                }
            } catch (e) { console.error(e); }
        }

        // --- DASHBOARD ---
        async function loadStats() {
            try {
                // Fetch stats numbers
                const res = await fetch('api_orders.php?action=get_stats');
                const data = await res.json();
                if (data.success) {
                    const s = data.stats;
                    document.getElementById('dash-total-orders').textContent = s.total_orders;
                    document.getElementById('dash-pending-orders').textContent = s.pending_orders;
                    document.getElementById('dash-completed-orders').textContent = s.completed_orders;
                    document.getElementById('dash-revenue').textContent = parseFloat(s.total_revenue).toFixed(2) + ' ETB';
                }
                
                // Fetch Charts
                const cRes = await fetch('api_analytics.php?action=get_charts');
                const cData = await cRes.json();
                if (cData.success) {
                    renderCharts(cData);
                }
                
                // Load Deposit Charts
                loadDepositCharts();
                

            } catch (e) {}
        }
        

        
        async function loadDepositCharts() {
            try {
                const period = document.getElementById('deposit-period')?.value || '7days';
                const res = await fetch(`api_deposit_analytics.php?action=get_deposit_charts&period=${period}`);
                const data = await res.json();
                
                if (data.success) {
                    // Update stats
                    document.getElementById('deposit-total').textContent = data.stats.total_deposits || '0';
                    document.getElementById('deposit-successful').textContent = data.stats.successful_deposits || '0';
                    document.getElementById('deposit-pending').textContent = data.stats.pending_deposits || '0';
                    document.getElementById('deposit-revenue').textContent = data.stats.total_revenue ? 
                        parseFloat(data.stats.total_revenue).toFixed(2) + ' ETB' : '0 ETB';
                    
                    // Render charts
                    renderDepositCharts(data.charts);
                    
                    // Render top depositors
                    renderTopDepositors(data.top_depositors);
                }
            } catch (e) {
                console.error('Failed to load deposit charts:', e);
            }
        }
        
        function renderDepositCharts(data) {
            if (!window.Chart || !data) return;
            
            // --- Trend Chart ---
            const ctx1 = document.getElementById('depositTrendChart');
            if (ctx1) {
                // Robustly get existing chart instance
                const existingChart = Chart.getChart(ctx1);
                
                if (existingChart) {
                    existingChart.data.labels = data.labels || [];
                    existingChart.data.datasets[0].data = data.datasets.deposit_counts || [];
                    existingChart.data.datasets[1].data = data.datasets.deposit_amounts || [];
                    existingChart.update();
                } else {
                    new Chart(ctx1, {
                        type: 'line',
                        data: {
                            labels: data.labels || [],
                            datasets: [{
                                label: 'Deposit Count',
                                data: data.datasets.deposit_counts || [],
                                borderColor: '#00CC66',
                                backgroundColor: 'rgba(0, 204, 102, 0.1)',
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'y',
                            }, {
                                label: 'Deposit Amount (ETB)',
                                data: data.datasets.deposit_amounts || [],
                                borderColor: '#FFD700',
                                backgroundColor: 'rgba(255, 215, 0, 0.1)',
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'y1',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { display: true, position: 'top' },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                if (context.datasetIndex === 1) {
                                                     label += parseFloat(context.parsed.y).toFixed(2) + ' ETB';
                                                } else {
                                                     label += context.parsed.y;
                                                }
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: { 
                                    type: 'linear', 
                                    display: true, 
                                    position: 'left',
                                    title: { display: true, text: 'Count' },
                                    grid: { color: 'rgba(255,255,255,0.05)' }
                                },
                                y1: { 
                                    type: 'linear', 
                                    display: true, 
                                    position: 'right', 
                                    grid: { drawOnChartArea: false },
                                    title: { display: true, text: 'Amount (ETB)' },
                                    ticks: {
                                        callback: function(value) { return value + ' ETB'; }
                                    }
                                },
                                x: {
                                    grid: { color: 'rgba(255,255,255,0.05)' }
                                }
                            }
                        }
                    });
                }
            }

            // --- Success Rate Chart ---
            const ctx2 = document.getElementById('depositSuccessChart');
            if (ctx2) {
                const existingChart2 = Chart.getChart(ctx2);
                
                if (existingChart2) {
                    existingChart2.data.labels = data.labels || [];
                    existingChart2.data.datasets[0].data = data.datasets.success_rates || [];
                    existingChart2.update();
                } else {
                    new Chart(ctx2, {
                        type: 'bar',
                        data: {
                            labels: data.labels || [],
                            datasets: [{
                                label: 'Success Rate (%)',
                                data: data.datasets.success_rates || [],
                                backgroundColor: '#4EA8DE',
                                borderRadius: 4,
                                barPercentage: 0.6
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: true, position: 'top' },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.parsed.y + '% Success Rate';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    grid: { color: 'rgba(255,255,255,0.05)' },
                                    ticks: {
                                        callback: function(value) {
                                            return value + '%';
                                        }
                                    }
                                },
                                x: {
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
            }
        }
        
        function renderTopDepositors(depositors) {
            const container = document.getElementById('top-depositors-list');
            if (!container) return;
            
            if (!depositors || depositors.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-secondary);">No deposit data available</div>';
                return;
            }
            
            container.innerHTML = depositors.map((d, index) => {
                const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `#${index + 1}`;
                return `
                    <div class="card" style="padding: 12px; margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 20px;">${medal}</span>
                            <div>
                                <div style="font-weight: bold; color: var(--text-primary);">User ${d.user_id}</div>
                                <div style="font-size: 11px; color: var(--text-secondary);">${d.deposit_count} deposits</div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: bold; color: var(--success); font-size: 16px;">${parseFloat(d.total_deposited).toFixed(2)} ETB</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function renderCharts(data) {
            if (!window.Chart) return;
            
            // Validate data
            if (!data || !data.labels || !data.datasets) {
                console.error('Invalid chart data received:', data);
                return;
            }
            
            // Main Chart (Orders & Revenue)
            const mainCanvas = document.getElementById('mainChart');
            if (mainCanvas) {
                const existingMain = Chart.getChart(mainCanvas);
                
                if (existingMain) {
                    // Update existing chart
                    existingMain.data.labels = data.labels || [];
                    existingMain.data.datasets[0].data = data.datasets.orders || [];
                    existingMain.data.datasets[1].data = data.datasets.revenue || [];
                    existingMain.update();
                } else {
                    // Create fresh chart
                    new Chart(mainCanvas, {
                        type: 'line',
                        data: {
                            labels: data.labels || [],
                            datasets: [{
                                label: 'Orders',
                                data: data.datasets.orders || [],
                                borderColor: '#4EA8DE',
                                backgroundColor: 'rgba(78, 168, 222, 0.1)',
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'y',
                            }, {
                                label: 'Revenue (ETB)',
                                data: data.datasets.revenue || [],
                                borderColor: '#5E60CE',
                                backgroundColor: 'rgba(94, 96, 206, 0.1)',
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'y1',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            animation: { duration: 0 }, 
                            plugins: {
                                legend: { display: true, position: 'top' }
                            },
                            scales: {
                                y: { 
                                    type: 'linear', 
                                    display: true, 
                                    position: 'left',
                                    title: { display: true, text: 'Orders' },
                                    grid: { color: 'rgba(255,255,255,0.05)' }
                                },
                                y1: { 
                                    type: 'linear', 
                                    display: true, 
                                    position: 'right', 
                                    grid: { drawOnChartArea: false },
                                    title: { display: true, text: 'Revenue (ETB)' }
                                },
                                x: {
                                    grid: { color: 'rgba(255,255,255,0.05)' }
                                }
                            }
                        }
                    });
                }
            }
            
            // User Chart
            const userCanvas = document.getElementById('userChart');
            if (userCanvas) {
                const existingUser = Chart.getChart(userCanvas);
                
                if (existingUser) {
                    existingUser.data.labels = data.labels || [];
                    existingUser.data.datasets[0].data = data.datasets.users || [];
                    existingUser.update();
                } else {
                    new Chart(userCanvas, {
                        type: 'bar',
                        data: {
                            labels: data.labels || [],
                            datasets: [{
                                label: 'New Users',
                                data: data.datasets.users || [],
                                backgroundColor: '#48BFE3',
                                borderRadius: 4
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            animation: { duration: 0 },
                            plugins: {
                                legend: { display: true, position: 'top' }
                            }
                        }
                    });
                }
            }
        }

        // --- ORDERS ---
        async function fetchOrders(quiet = false) {
            const list = document.getElementById('orders-list');
            const search = document.getElementById('order-search').value;
            const status = document.getElementById('order-status').value;
            
            if (!quiet) document.getElementById('orders-loading').style.display = 'block';
            if (!quiet) list.innerHTML = '';
            
            const formData = new FormData();
            formData.append('action', 'get_orders');
            formData.append('search', search);
            formData.append('status', status);
            
            try {
                const res = await fetch('api_orders.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success && data.orders.length > 0) {
                    list.innerHTML = data.orders.map(o => `
                        <tr>
                            <td class="font-mono text-xs" data-label="ID">#${o.id}</td>
                            <td onclick="openUserModal('${o.user_id}')" style="cursor:pointer;" class="hover:text-accent" data-label="User">
                                <div class="text-sm font-bold">${o.user_id}</div>
                                <div class="text-xs text-secondary">${o.user_name || 'Unknown'}</div>
                            </td>
                            <td class="text-xs" data-label="Service">
                                <span class="font-mono text-accent">#${o.service_id}</span>
                            </td>
                            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" data-label="Link">
                                <a href="${o.link}" target="_blank" class="text-xs text-primary underline">${o.link}</a>
                            </td>
                            <td class="text-sm" data-label="Quantity">${o.quantity}</td>
                            <td class="font-mono text-xs" data-label="Charge">${parseFloat(o.charge).toFixed(4)} ETB</td>
                            <td data-label="Status"><span class="status-badge status-${o.status}">${o.status}</span></td>
                            <td class="text-xs text-secondary" data-label="Date">${new Date(o.created_at).toLocaleDateString()}</td>
                            <td data-label="Actions">
                                ${(o.status !== 'cancelled' && o.status !== 'completed') ? 
                                    `<button onclick="cancelOrder(${o.id})" class="btn btn-danger btn-sm">Cancel</button>`
                                    : '-'}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    if (!quiet) list.innerHTML = '<tr><td colspan="9" class="text-center" style="padding: 20px; color: var(--text-secondary);">No orders found</td></tr>';
                }
            } catch (e) {
                console.error(e);
            } finally {
                document.getElementById('orders-loading').style.display = 'none';
            }
        }

        async function fetchDeposits() {
            const list = document.getElementById('deposits-list');
            if(!list) return;
            
            list.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
            
            try {
                const res = await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'get_deposits' })
                });
                const data = await res.json();
                
                if (data.success && data.deposits.length > 0) {
                    list.innerHTML = data.deposits.map(d => `
                        <tr>
                            <td data-label="ID">#${d.id}</td>
                            <td data-label="User">
                                <div class="font-bold text-sm">${d.first_name || 'Unknown'}</div>
                                <div class="text-xs text-secondary">${d.user_id}</div>
                            </td>
                            <td class="font-bold text-success" data-label="Amount">+${parseFloat(d.amount).toFixed(2)} ETB</td>
                            <td class="font-mono text-xs" data-label="Ref ID">${d.reference_id}</td>
                            <td data-label="Status"><span class="status-badge status-${d.status}">${d.status}</span></td>
                            <td class="text-xs text-secondary" data-label="Date">${new Date(d.created_at).toLocaleString()}</td>
                        </tr>
                    `).join('');
                } else {
                    list.innerHTML = '<tr><td colspan="6" class="text-center" style="padding: 20px;">No deposits found</td></tr>';
                }
            } catch (e) {
                console.error(e);
                list.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading deposits</td></tr>';
            }
        }
        
        // --- USERS ---
        let usersPollingInterval = null;

        
        // Start/stop polling for online status
        function startUsersPolling() {
            if (usersPollingInterval) clearInterval(usersPollingInterval);
            usersPollingInterval = setInterval(() => {
                // Quiet refresh - just update online status
                fetchUsers();
            }, 8000); // 8s for aggressive real-time
        }
        
        function stopUsersPolling() {
            if (usersPollingInterval) {
                clearInterval(usersPollingInterval);
                usersPollingInterval = null;
            }
        }
        async function cancelOrder(id) {
            if (!confirm(`Are you sure you want to cancel Order #${id}? The user will be refunded.`)) return;
            
            const formData = new FormData();
            formData.append('action', 'cancel_order');
            formData.append('order_id', id);
            
            const res = await fetch('api_orders.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                alert(data.message);
                fetchOrders(); // Refresh
                loadStats(); // Update stats
            } else {
                alert('Error: ' + data.error);
            }
        }

        // --- SERVICES ---
        document.getElementById('service-search').addEventListener('input', (e) => {
            renderServices(e.target.value);
        });

        function setServiceFilter(filter) {
            currentServiceFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === filter);
            });
            renderServices(document.getElementById('service-search').value);
        }

        function renderServices(query = '') {
            const q = query.toLowerCase().trim();
            const resDiv = document.getElementById('services-results');
            resDiv.innerHTML = '';
            
            // If filter is 'all' and query is short, don't show everything (too many)
            if (currentServiceFilter === 'all' && q.length < 2) {
                resDiv.innerHTML = '<div style="text-align:center; color:var(--text-secondary); padding:20px;">Type to search services...</div>';
                return;
            }
            
            const terms = q.split(/\s+/).filter(t => t.length > 0);
            
            let matches = allServices.filter(s => {
                if (q.length === 0) return true; // Show all if no query (unless filter logic above stops it)
                if (s.service.toString() === q) return true;
                
                const name = s.name.toLowerCase();
                // STRICT AND LOGIC: Every term must match
                return terms.every(t => name.includes(t));
            });
            
            // Sort by ID naturally
            if (q.length > 0) {
                 // No complex sorting needed for strict match usually, but maybe exact matches first
            }

            // Apply filter
            if (currentServiceFilter === 'recommended') {
                matches = matches.filter(s => recommendedIds.has(parseInt(s.service)));
            } else if (currentServiceFilter === 'hidden') {
                matches = matches.filter(s => hiddenServiceIds.has(parseInt(s.service)));
            }
            
            if (matches.length === 0) {
                resDiv.innerHTML = '<div style="text-align:center; color:var(--text-secondary); padding:20px;">No services match your filters.</div>';
                return;
            }

            // Limit results if searching
            const displayList = matches.slice(0, 50);
            
            displayList.forEach(s => {
                const sid = parseInt(s.service);
                const isRec = recommendedIds.has(sid);
                const isHidden = hiddenServiceIds.has(sid);
                
                const el = document.createElement('div');
                el.className = 'card';
                el.style.padding = '12px';
                el.style.marginBottom = '0';
                el.style.display = 'flex';
                el.style.justifyContent = 'space-between';
                el.style.alignItems = 'center';
                
                el.innerHTML = `
                    <div style="flex: 1; min-width: 0;">
                        <div class="font-bold text-sm truncate">
                            <span style="color:var(--accent-primary)">#${sid}</span> ${s.name}
                            ${isHidden ? '<span class="status-badge status-cancelled" style="margin-left:8px; font-size:10px;">HIDDEN</span>' : ''}
                            ${isRec ? '<span class="status-badge status-completed" style="margin-left:8px; font-size:10px;">TOP</span>' : ''}
                        </div>
                        <div class="text-xs text-secondary truncate">${s.category}</div>
                    </div>
                    
                    <div style="display:flex; align-items:center; gap:8px; margin: 0 12px;">
                        <div class="form-group mb-0" style="width: 120px;">
                            <input type="text" 
                                   value="${s.average_time || ''}" 
                                   placeholder="Avg Time..." 
                                   class="form-input" 
                                   style="padding: 4px 8px; font-size: 11px; height: 30px;"
                                   onchange="updateAverageTime(${sid}, this.value)">
                        </div>
                    </div>

                    <div style="display:flex; gap:6px;">
                       <button onclick="toggleHiddenStatus(${sid})" class="btn btn-sm ${isHidden ? 'btn-secondary' : 'btn-danger'}" style="padding: 4px 8px; font-size: 11px;">
                            ${isHidden ? 'Unhide' : 'Hide'}
                       </button>
                       <button onclick="toggleService(${sid})" class="btn btn-sm ${isRec ? 'btn-danger' : 'btn-success'}" style="padding: 4px 8px; font-size: 11px;">
                            ${isRec ? 'Remove' : 'Add'} Rec
                       </button>
                    </div>
                `;
                resDiv.appendChild(el);
            });
        }

        async function toggleService(id) {
            const isAdd = !recommendedIds.has(id);
            const action = isAdd ? 'add_recommended' : 'remove_recommended';
            
            const res = await fetch('api_general.php', {
                method: 'POST',
                body: JSON.stringify({ action, service_id: id })
            });
            const data = await res.json();
            if (data.success) {
                if (isAdd) recommendedIds.add(id); else recommendedIds.delete(id);
                document.getElementById('bulk-ids').value = Array.from(recommendedIds).join(', ');
                // Trigger input event to refresh list
                document.getElementById('service-search').dispatchEvent(new Event('input'));
            }
        }

        async function toggleHiddenStatus(id) {
            const isHidden = hiddenServiceIds.has(id);
            const action = isHidden ? 'unhide_service' : 'hide_service';
            
            const res = await fetch('api_general.php', {
                method: 'POST',
                body: JSON.stringify({ action, service_id: id })
            });
            const data = await res.json();
            if (data.success) {
                if (isHidden) hiddenServiceIds.delete(id); else hiddenServiceIds.add(id);
                renderServices(document.getElementById('service-search').value);
            }
        }

        async function updateAverageTime(id, time) {
            try {
                const res = await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'update_average_time', service_id: id, average_time: time })
                });
                const data = await res.json();
                if (data.success) {
                    // Update local data so it persists during current session filtering
                    const service = allServices.find(s => parseInt(s.service) === id);
                    if (service) service.average_time = time;
                    console.log(`Updated average time for service ${id}`);
                }
            } catch (e) {
                console.error('Failed to update average time:', e);
            }
        }
        
        async function bulkUpdateServices() {
            const ids = document.getElementById('bulk-ids').value;
            if (!confirm('This will display ONLY these services on the recommended list. Proceed?')) return;
            
            const res = await fetch('api_general.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'bulk_update_recommended', ids })
            });
            if ((await res.json()).success) {
                alert('Updated successfully');
                loadGeneralData();
            }
        }

        // --- ALERTS ---
        async function loadUserAlerts() {
            const uid = document.getElementById('alert-user-id').value;
            if (!uid) return alert('Enter a User ID');
            
            const res = await fetch('api_general.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'get_user_alerts', user_id: uid })
            });
            const data = await res.json();
            
            const div = document.getElementById('alert-list');
            document.getElementById('alert-history').style.display = 'block';
            div.innerHTML = '';
            
            if (data.success && data.alerts.length > 0) {
                data.alerts.forEach(a => {
                    div.innerHTML += `
                        <div style="background:var(--bg-secondary); padding:8px; border-radius:8px; margin-bottom: 8px; display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <div class="text-xs text-secondary">${a.created_at}</div>
                                <div class="text-sm" id="alert-msg-${a.id}">${a.message}</div>
                            </div>
                            <div style="display:flex; gap:4px; margin-left:8px;">
                                <button onclick="editAlert(${a.id})" class="btn btn-secondary" style="padding:2px 8px; font-size:11px;">Edit</button>
                                <button onclick="deleteAlert(${a.id})" class="btn btn-danger" style="padding:2px 8px; font-size:11px;">Del</button>
                            </div>
                        </div>
                    `;
                });
            } else {
                div.innerHTML = '<div class="text-secondary text-sm">No history found.</div>';
            }
        }

        async function editAlert(id) {
            const currentMsg = document.getElementById('alert-msg-' + id).textContent;
            const newMsg = prompt("Edit Message:", currentMsg);
            if (newMsg !== null && newMsg.trim() !== "") {
                 await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'update_alert', id, message: newMsg })
                });
                loadUserAlerts();
            }
        }

        async function deleteAlert(id) {
            if(!confirm("Delete this alert?")) return;
            await fetch('api_general.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'delete_alert', id })
            });
            loadUserAlerts();
        }
        
        // --- USERS ---
        async function fetchUsers() {
            const search = document.getElementById('user-search').value;
            const filter = document.getElementById('user-filter').value;
            const sort = document.getElementById('user-sort').value;
            const dir = document.getElementById('user-dir').value;
            const grid = document.getElementById('users-grid');
            
            try {
                const res = await fetch('api_users.php', { 
                    method: 'POST', 
                    body: new URLSearchParams({ action: 'get_users', search, filter, sort, dir }) 
                });
                const data = await res.json();
                
                if (data.success && data.users.length > 0) {
                    // Update header with online count if possible
                    const headerSpan = document.querySelector('#tab-users .card-header span');
                    if (headerSpan) {
                         headerSpan.innerHTML = `User Management <span style="font-size:12px;color:#00cc66;margin-left:8px;">● ${data.online_count || 0} online</span>`;
                    }

                    let html = `
                    <div class="table-container">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs text-gray-400 border-b border-white/10" style="background: rgba(0,0,0,0.2);">
                                    <th class="p-3">USER INFO</th>
                                    <th class="p-3">USERNAME</th>
                                    <th class="p-3">BALANCE / DEPOSIT</th>
                                    <th class="p-3">ORDERS / SPENT</th>
                                    <th class="p-3 text-right">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                    `;
                    
                    html += data.users.map(u => {
                        const displayName = (u.first_name || u.last_name) ? `${u.first_name || ''} ${u.last_name || ''}`.trim() : (u.username || 'User');
                        const username = u.username ? `@${u.username}` : (u.tg_username ? `@${u.tg_username}` : 'No Handle');
                        const phoneNumber = u.phone_number || null;
                        // Fallback block status to 0 if undefined
                        const isBlocked = (u.is_blocked == 1);
                        const blockStatusParam = u.is_blocked ? 1 : 0;
                        const photoUrl = u.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=random`;

                        return `
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-3" data-label="User">
                                <div class="flex items-center gap-3 md:justify-start justify-end">
                                    <div class="relative">
                                        <img src="${photoUrl}" class="w-10 h-10 rounded-full bg-gray-800 object-cover border border-white/10" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=random'">
                                        <div class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-[#1a1a24] ${u.is_online ? 'bg-green-500 shadow-[0_0_8px_#22c55e]' : 'bg-gray-500'}"></div>
                                    </div>
                                    <div class="flex flex-col text-right md:text-left">
                                        <span class="font-medium text-white text-sm cursor-pointer hover:text-accent" onclick="openUserModal('${u.tg_id}')">${displayName}</span>
                                        <div class="flex items-center gap-1">
                                            <span class="text-[10px] text-gray-500 cursor-pointer hover:text-white" onclick="openUserModal('${u.tg_id}')">ID: ${u.tg_id}</span>
                                            <span class="text-[9px] text-secondary opacity-60">• Seen: ${u.last_seen ? new Date(u.last_seen).toLocaleTimeString() : 'Never'}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                             <td class="p-3" data-label="Username">
                                <div class="text-xs text-gray-400">${username}</div>
                                ${phoneNumber ? `<a href="tel:${phoneNumber}" class="text-xs text-green-400 hover:text-green-300">📞 ${phoneNumber}</a>` : '<span class="text-[10px] text-gray-600">No Phone</span>'}
                            </td>
                             <td class="p-3" data-label="Balance">
                                <div class="text-green-400 font-mono text-sm">${parseFloat(u.balance || 0).toFixed(2)} ETB</div>
                                <div class="text-[10px] text-secondary">
                                    ${u.last_deposit_at ? 'Dep: '+new Date(u.last_deposit_at).toLocaleDateString() : 'No deposits'}
                                </div>
                            </td>
                            <td class="p-3 text-sm" data-label="Stats">
                                <div class="text-white">${u.total_orders || 0} orders</div>
                                <div class="text-xs text-gray-400">${parseFloat(u.total_spent || 0).toFixed(2)} spent</div>
                            </td>
                            <td class="p-3 text-right" data-label="Actions">
                                <div class="flex justify-end gap-2 flex-wrap">
                                    ${phoneNumber ? `<a href="https://wa.me/${phoneNumber.replace(/[^0-9]/g, '')}" target="_blank" class="px-3 py-1.5 text-xs font-medium rounded bg-green-500/10 hover:bg-green-500/20 border border-green-500/30 text-green-400 transition-colors" title="WhatsApp">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </a>` : ''}
                                    <button onclick="${u.username ? `window.open('https://t.me/${u.username}', '_blank')` : `startUserChat('${u.tg_id}', '${(u.first_name || 'User').replace(/'/g, "\\\\'")}', '${u.photo_url || ''}'); alert('User has no @username. Use Chat/DM or WhatsApp.')`}" class="px-3 py-1.5 text-xs font-medium rounded bg-[#0088cc]/10 hover:bg-[#0088cc]/20 border border-[#0088cc]/30 text-[#0088cc] transition-colors" title="${u.username ? 'Open Telegram Profile' : 'No username - will open Chat instead'}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.198 2.433a2.242 2.242 0 0 0-1.022.215l-8.609 3.33c-2.068.8-4.133 1.598-5.724 2.21a405.15 405.15 0 0 1-2.849 1.09c-.42.147-.99.332-1.473.901-.728.968.835 1.798 1.563 2.246 1.541.688 3.282 1.9 5.02 2.668 1.914 1.258 3.827 2.645 5.766 3.978.43.327 1.487 1.255 2.152.88 1.636-.262 2.05-1.921 2.384-3.568.536-2.618 1.43-8.83 1.907-11.83.187-1.125-.26-1.55-1.115-2.12z"/></svg>
                                    </button>
                                    <button onclick="startUserChat('${u.tg_id}', '${displayName.replace(/'/g, "\\'")}', '${photoUrl}')" class="px-3 py-1.5 text-xs font-medium rounded bg-accent/20 hover:bg-accent/30 border border-accent/30 text-accent transition-colors">
                                        Chat / DM
                                    </button>
                                    <button onclick="editBalance('${u.tg_id}')" class="px-3 py-1.5 text-xs font-medium rounded bg-gray-800 hover:bg-gray-700 border border-gray-700 text-white transition-colors">
                                        Balance
                                    </button>
                                    <button onclick="toggleUserBlock('${u.tg_id}', ${u.is_blocked == 1 ? 1 : 0})" class="btn btn-sm ${u.is_blocked == 1 ? 'btn-unblock' : 'btn-block'}">
                                        ${u.is_blocked == 1 ? 'Unblock' : 'Block'}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        `;
                    }).join('');
                    
                    html += `</tbody></table></div>`;
                    grid.innerHTML = html;

                } else {
                    grid.innerHTML = '<div class="empty-state">No users found for this search.</div>';
                }
            } catch (e) {
                grid.innerHTML = '<div class="empty-state text-danger">Error fetching user data. Check console.</div>';
                console.error(e);
            }
        }

        
        async function editBalance(uid) {
            const amt = prompt("Enter amount to ADD (use negative to subtract):", "0");
            if (!amt) return;
            
            await fetch('api_users.php', {
                method: 'POST', 
                body: new URLSearchParams({ action: 'update_balance', user_id: uid, amount: amt, type: 'add' })
            });
            fetchUsers();
            
            // Refresh modal if open
            if (document.getElementById('user-modal-overlay')?.classList.contains('active')) {
                openUserModal(uid);
            }
        }
        
        async function copyUserId(uid) {
            try {
                await navigator.clipboard.writeText(uid);
                alert('Copied ID: ' + uid);
            } catch (e) {
                prompt("Copy ID:", uid);
            }
        }

        function openBotMessage(uid) {
            loadTab('bot');
            document.getElementById('direct-tg-id').value = uid;
            document.getElementById('direct-message-text').focus();
        }
        
        async function toggleUserBlock(uid, currentStatus) {
            // Logic Fix: Inverse the status
            const newStatus = currentStatus == 1 ? 0 : 1;
            const actionText = newStatus == 1 ? 'BLOCK' : 'UNBLOCK';
            
            if (!confirm(`Are you sure you want to ${actionText} this user?`)) return;
            
            const res = await fetch('api_users.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'toggle_block', user_id: uid, is_blocked: newStatus })
            });
            
            const data = await res.json();
            if (data.success) {
                fetchUsers();
                if (document.getElementById('user-modal-overlay')?.classList.contains('active')) {
                    openUserModal(uid);
                }
            } else {
                alert('Error: ' + (data.error || 'Failed to update user status'));
            }
        }

        // --- HOLIDAYS ---
        async function fetchHolidays() {
            const res = await fetch('api_discounts.php?action=get_holidays');
            const data = await res.json();
            const list = document.getElementById('holidays-list');
            list.innerHTML = '';
            
            if (data.success && data.holidays.length) {
                list.innerHTML = data.holidays.map(h => `
                    <tr>
                        <td class="font-bold">${h.name}</td>
                        <td>${h.start_date}</td>
                        <td>${h.end_date}</td>
                        <td class="text-accent">${h.discount_percent}%</td>
                        <td><button onclick="toggleHoliday(${h.id}, '${h.status}')" class="btn btn-sm ${h.status === 'active' ? 'btn-success' : 'btn-secondary'}">${h.status}</button></td>
                        <td><button onclick="deleteHoliday(${h.id})" class="btn btn-danger btn-sm">Del</button></td>
                    </tr>
                `).join('');
            } else {
                list.innerHTML = '<tr><td colspan="6" class="text-center">No events found</td></tr>';
            }
            
            // Also render the reference calendar
            renderHolidayCalendar();
        }
        
        function renderHolidayCalendar(filter = 'all') {
            const holidays = getUpcomingHolidays(30); // Get next 30 holidays
            const container = document.getElementById('holiday-calendar-list');
            
            let filtered = holidays;
            if (filter !== 'all') {
                filtered = holidays.filter(h => h.type === filter);
            }
            
            const typeColors = {
                'ethiopian': '#FFD700',
                'international': '#4EA8DE',
                'islamic': '#00CC66',
                'christian': '#9B59B6',
                'shopping': '#FF4757'
            };
            
            const typeIcons = {
                'ethiopian': '🇪🇹',
                'international': '🌍',
                'islamic': '☪️',
                'christian': '✝️',
                'shopping': '🛒'
            };
            
            container.innerHTML = filtered.map(holiday => {
                const date = new Date(holiday.date);
                const daysUntil = Math.ceil((date - new Date()) / (1000 * 60 * 60 * 24));
                const color = typeColors[holiday.type] || '#888';
                const icon = typeIcons[holiday.type] || '📅';
                
                return `
                    <div class="card" style="cursor: pointer; padding: 12px; margin-bottom: 0; border-left: 4px solid ${color}; transition: all 0.2s;" 
                         onclick="selectHolidayTemplate('${holiday.name}', '${holiday.date}', '${holiday.type}')"
                         onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.3)';"
                         onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div style="font-size: 20px;">${icon}</div>
                            <div style="background: ${color}; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: bold;">
                                ${daysUntil === 0 ? 'TODAY' : daysUntil === 1 ? 'TOMORROW' : `${daysUntil} DAYS`}
                            </div>
                        </div>
                        <div style="font-weight: bold; font-size: 13px; margin-bottom: 4px; color: var(--text-primary);">${holiday.name}</div>
                        <div style="font-size: 11px; color: var(--text-secondary);">${date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</div>
                        <div style="margin-top: 6px; font-size: 10px; opacity: 0.7; text-transform: capitalize;">${holiday.type}</div>
                    </div>
                `;
            }).join('');
            
            if (filtered.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-secondary);">No holidays found for this filter</div>';
            }
        }
        
        function filterHolidayCalendar() {
            const filter = document.getElementById('holiday-filter').value;
            renderHolidayCalendar(filter);
        }
        
        function selectHolidayTemplate(name, date, type) {
            // Auto-fill the form
            const holidayDate = new Date(date);
            const endDate = new Date(date);
            endDate.setDate(endDate.getDate() + 1); // Default to 2-day event
            
            document.getElementById('hol-name').value = name + ' Sale';
            document.getElementById('hol-start').value = date;
            document.getElementById('hol-end').value = endDate.toISOString().split('T')[0];
            
            // Suggest discount based on holiday type
            const suggestedDiscounts = {
                'shopping': 30,
                'international': 20,
                'ethiopian': 25,
                'islamic': 20,
                'christian': 20
            };
            document.getElementById('hol-percent').value = suggestedDiscounts[type] || 15;
            
            // Scroll to form
            document.getElementById('hol-name').scrollIntoView({ behavior: 'smooth', block: 'center' });
            document.getElementById('hol-name').focus();
            
            // Visual feedback
            document.getElementById('hol-name').style.background = 'rgba(108, 92, 231, 0.1)';
            setTimeout(() => {
                document.getElementById('hol-name').style.background = '';
            }, 1000);
        }
        
        async function addHoliday() {
            const name = document.getElementById('hol-name').value;
            const start = document.getElementById('hol-start').value;
            const end = document.getElementById('hol-end').value;
            const percent = document.getElementById('hol-percent').value;
            
            if (!name || !start || !end) return alert('Fill all fields');
            
            await fetch('api_discounts.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'add_holiday', name, start_date: start, end_date: end, percent })
            });
            fetchHolidays();
        }
        
        async function toggleHoliday(id, status) {
            await fetch('api_discounts.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'toggle_status', id, status })
            });
            fetchHolidays();
        }
        
        async function deleteHoliday(id) {
            if(!confirm('Delete?')) return;
            await fetch('api_discounts.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'delete_holiday', id })
            });
            fetchHolidays();
        }

        // --- SETTINGS ---
        async function saveMarquee() {
            const text = document.getElementById('marquee-text').value;
            const enabled = document.getElementById('marquee-enabled').checked;
            
            const res = await fetch('api_general.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'update_marquee', text, enabled })
            });
            if ((await res.json()).success) alert('Settings Saved!');
        }
        
        async function saveRateMultiplier() {
            const multiplier = document.getElementById('rate-multiplier').value;
            if (!multiplier || multiplier < 1) return alert('Please enter a valid rate multiplier (minimum 1)');
            
            const res = await fetch('api_general.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'update_rate_multiplier', multiplier })
            });
            if ((await res.json()).success) alert('Rate Multiplier Saved!');
        }

        async function updateMaintenance() {
            const mode = document.getElementById('maintenance-mode-toggle').checked;
            const allowed_ids = document.getElementById('maintenance-allowed-ids').value;
            
            try {
                const res = await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'update_maintenance', mode: mode, allowed_ids })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Emergency settings updated!', 'success');
                }
            } catch (e) {
                console.error(e);
                showToast('Failed to update emergency settings', 'error');
            }
        }

        async function loadSettings() {
            try {
                const res = await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'get_settings' })
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('marquee-text').value = data.settings.marquee_text || '';
                    document.getElementById('marquee-enabled').checked = data.settings.marquee_enabled === '1';
                    document.getElementById('rate-multiplier').value = data.settings.rate_multiplier || '400';
                    
                    // Emergency Settings
                    document.getElementById('maintenance-mode-toggle').checked = data.settings.maintenance_mode === '1';
                    document.getElementById('maintenance-allowed-ids').value = data.settings.maintenance_allowed_ids || '';
                }
            } catch (e) { console.error(e); }
        }

        // --- ANALYTICS TAB ---
        async function loadAnalytics() {
            // Load System Health
            loadSystemHealth();
            // Load Top Customers
            loadTopCustomers();
            // Load Popular Services
            loadPopularServices();
        }
        
        async function loadSystemHealth() {
            try {
                const res = await fetch('api_analytics.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'system_health' })
                });
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('health-services').textContent = data.services_count || '0';
                    document.getElementById('health-active-users').textContent = data.active_users_24h || '0';
                    document.getElementById('health-api').textContent = data.api_status || 'OK';
                    document.getElementById('health-api').className = 'stat-value ' + (data.api_status === 'OK' ? 'success' : 'danger');
                    document.getElementById('health-db').textContent = data.db_size || 'N/A';
                }
            } catch (e) {
                console.error('Health check error:', e);
            }
        }
        
        async function loadTopCustomers() {
            const container = document.getElementById('top-customers-list');
            try {
                const res = await fetch('api_analytics.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'top_customers', limit: 10 })
                });
                const data = await res.json();
                
                if (data.success && data.customers.length) {
                    container.innerHTML = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Orders</th>
                                    <th>Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.customers.map((c, i) => `
                                    <tr>
                                        <td>${i + 1}</td>
                                        <td>
                                            <div style="font-weight:500;">${c.name || 'User'}</div>
                                            <div style="font-size:10px; color:var(--text-muted);">ID: ${c.tg_id}</div>
                                        </td>
                                        <td>${c.order_count}</td>
                                        <td style="color:var(--accent-success); font-weight:600;">${parseFloat(c.total_spent).toFixed(2)} ETB</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    container.innerHTML = '<div class="empty-state">No customer data</div>';
                }
            } catch (e) {
                container.innerHTML = '<div class="empty-state text-danger">Error loading data</div>';
            }
        }
        
        async function loadPopularServices() {
            const container = document.getElementById('popular-services-list');
            try {
                const res = await fetch('api_analytics.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'popular_services', limit: 10 })
                });
                const data = await res.json();
                
                if (data.success && data.services.length) {
                    container.innerHTML = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.services.map(s => `
                                    <tr>
                                        <td><span style="font-family:monospace; font-size:11px;">${s.service_id}</span></td>
                                        <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${s.service_name || ''}">${s.service_name || 'Service #' + s.service_id}</td>
                                        <td style="font-weight:600;">${s.order_count}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    container.innerHTML = '<div class="empty-state">No service data</div>';
                }
            } catch (e) {
                container.innerHTML = '<div class="empty-state text-danger">Error loading data</div>';
            }
        }
        
        async function exportOrders() {
            try {
                const res = await fetch('api_analytics.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'export_orders' })
                });
                const blob = await res.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'paxyo_orders_' + new Date().toISOString().split('T')[0] + '.csv';
                a.click();
                window.URL.revokeObjectURL(url);
                alert('Orders exported successfully!');
            } catch (e) {
                alert('Export failed: ' + e.message);
            }
        }
        
        async function exportUsers() {
            try {
                const res = await fetch('api_analytics.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'export_users' })
                });
                const blob = await res.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'paxyo_users_' + new Date().toISOString().split('T')[0] + '.csv';
                a.click();
                window.URL.revokeObjectURL(url);
                alert('Users exported successfully!');
            } catch (e) {
                alert('Export failed: ' + e.message);
            }
        }
        
        async function refreshAllServices() {
            if (!confirm('This will refresh all services from the API. Continue?')) return;
            try {
                const res = await fetch('../get_service.php?refresh=1');
                const data = await res.json();
                alert(`Services synced! ${data.services?.length || 0} services loaded.`);
                loadAnalytics();
            } catch (e) {
                alert('Sync failed: ' + e.message);
            }
        }
        
        async function clearOrderCache() {
            if (!confirm('This will clear order status cache. Continue?')) return;
            try {
                const res = await fetch('api_analytics.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'clear_cache' })
                });
                const data = await res.json();
                alert(data.message || 'Cache cleared!');
            } catch (e) {
                alert('Failed: ' + e.message);
            }
        }

        // --- REAL-TIME SSE STREAM FOR ADMIN ---
        // SSE Removed as per request. Use manual refresh.

        // --- AI ASSISTANT ---
        function toggleAiChat() {
            const window = document.getElementById('ai-chat-window');
            window.classList.toggle('active');
            if (window.classList.contains('active')) {
                document.getElementById('ai-input').focus();
            }
        }

        async function sendAiMessage() {
            const input = document.getElementById('ai-input');
            const message = input.value.trim();
            if (!message) return;

            // Add user message to UI
            addChatMessage(message, 'user');
            input.value = '';

            // Show typing
            const typing = document.getElementById('chat-typing');
            typing.style.display = 'block';
            
            const messagesContainer = document.querySelector('.chat-messages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            try {
                const res = await fetch('api_ai_assistant.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'chat', message: message })
                });
                const data = await res.json();
                
                typing.style.display = 'none';
                
                if (data.success) {
                    addChatMessage(data.response, 'ai');
                } else {
                    addChatMessage("Sorry boss, I'm having a little brain fog. Maybe try again?", 'ai');
                }
            } catch (e) {
                typing.style.display = 'none';
                addChatMessage("Connection error with my primary brain. Check internet!", 'ai');
            }
        }

        function addChatMessage(text, sender) {
            const container = document.querySelector('.chat-messages');
            const msgDiv = document.createElement('div');
            msgDiv.className = `msg msg-${sender}`;
            
            // Handle basic markdown-ish like bolding
            let formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            msgDiv.innerHTML = formattedText;
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        // Handle Enter key
        setTimeout(() => {
            document.getElementById('ai-input')?.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendAiMessage();
            });
        }, 1000);

        // --- ALERTS ---
        async function sendAlert() {
            const userId = document.getElementById('alert-user-id').value;
            const message = document.getElementById('alert-message').value;
            
            if (!userId || !message) {
                alert('Please enter User ID and Message');
                return;
            }
            
            try {
                const res = await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'send_alert', user_id: userId, message })
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('Alert sent successfully!');
                    document.getElementById('alert-message').value = '';
                    loadUserAlerts();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                console.error(e);
                alert('Failed to send alert');
            }
        }
        
        async function loadUserAlerts() {
            const userId = document.getElementById('alert-user-id').value;
             if (!userId) return; // Silent return if empty
             
            const list = document.getElementById('alert-list');
            const historyDiv = document.getElementById('alert-history');
            
            try {
                const res = await fetch('api_general.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'get_user_alerts', user_id: userId })
                });
                const data = await res.json();
                
                if (data.success && data.alerts.length > 0) {
                    historyDiv.style.display = 'block';
                    list.innerHTML = data.alerts.map(a => `
                        <div class="card" style="padding: 12px; margin-bottom: 0;">
                            <div class="text-xs text-secondary mb-1">${new Date(a.created_at).toLocaleString()}</div>
                            <div class="text-sm">${a.message}</div>
                        </div>
                    `).join('');
                } else {
                    historyDiv.style.display = 'none';
                    list.innerHTML = '';
                }
            } catch (e) {
                console.error(e);
            }
        }

        // --- PRO FEATURES: USER MODAL ---
        async function openUserModal(userId) {
            const overlay = document.getElementById('user-modal-overlay');
            
            // Show loading state
            overlay.classList.add('active');
            document.getElementById('modal-user-content').innerHTML = '<div class="loading">Loading Profile...</div>';
            
            try {
                const res = await fetch('api_users.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'get_user_details', user_id: userId })
                });
                const data = await res.json();
                
                if (data.success) {
                    renderUserModal(data);
                } else {
                    document.getElementById('modal-user-content').innerHTML = `<div class="empty-state text-danger">${data.error}</div>`;
                }
            } catch (e) {
                console.error(e);
                document.getElementById('modal-user-content').innerHTML = '<div class="empty-state text-danger">Failed to load user data</div>';
            }
        }
        
        function closeUserModal() {
            document.getElementById('user-modal-overlay').classList.remove('active');
        }
        
        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeUserModal();
        });
        
        function renderUserModal(data) {
            const u = data.user;
            const stats = data.stats;
            const orders = data.orders;
            const logs = data.logs;
            
            const displayName = (u.first_name || u.last_name) ? `${u.first_name || ''} ${u.last_name || ''}`.trim() : (u.username || 'User');
            const photo = u.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=random`;
            
            const html = `
                <div class="user-profile-header">
                    <img src="${photo}" class="user-avatar-large shadow-lg">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <h2 class="text-2xl font-bold text-white">${displayName}</h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${u.is_online ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/20'}">
                                ${u.is_online ? '● ONLINE' : 'OFFLINE'}
                            </span>
                        </div>
                        <div class="text-secondary text-sm flex items-center gap-2">
                            <span class="text-accent font-medium">@${u.username || u.tg_username || 'no_handle'}</span>
                            <span class="opacity-30">•</span>
                            <span class="font-mono text-xs opacity-60">#${u.tg_id}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-black text-green-400 tabular-nums">${parseFloat(u.balance).toFixed(2)} ETB</div>
                        <div class="text-[10px] font-bold text-gray-500 tracking-wider">AVAILABLE BALANCE</div>
                    </div>
                </div>
                
                <div class="user-quick-stats">
                    <div class="stat-box">
                        <div class="text-2xl font-bold text-white mb-1">${stats.total_orders}</div>
                        <div class="text-[10px] font-bold text-secondary uppercase tracking-widest">Total Orders</div>
                    </div>
                    <div class="stat-box">
                        <div class="text-2xl font-bold text-white mb-1">${parseFloat(stats.total_spent).toFixed(2)} ETB</div>
                        <div class="text-[10px] font-bold text-secondary uppercase tracking-widest">Total Spent</div>
                    </div>
                    <div class="stat-box">
                        <div class="text-2xl font-bold text-white mb-1">${stats.active_orders}</div>
                        <div class="text-[10px] font-bold text-secondary uppercase tracking-widest">Active Orders</div>
                    </div>
                </div>
                
                <div class="user-action-grid">
                    <div onclick="editBalance('${u.tg_id}')" class="action-card">
                        <div class="text-2xl mb-2">💰</div>
                        <div class="text-xs font-bold text-white">Add Funds</div>
                        <div class="text-[9px] text-accent mt-1 opacity-60">Adjust Balance</div>
                    </div>
                    <div onclick="document.getElementById('alert-user-id').value='${u.tg_id}'; closeUserModal(); loadTab('alerts');" class="action-card">
                        <div class="text-2xl mb-2">📢</div>
                        <div class="text-xs font-bold text-white">Push Alert</div>
                        <div class="text-[9px] text-accent mt-1 opacity-60">Send Notification</div>
                    </div>
                    <div onclick="sendUserModalDirectMessage('${u.tg_id}', '${displayName}')" class="action-card" style="border-color: #0088cc44; background: rgba(0, 136, 204, 0.05);">
                        <div class="text-2xl mb-2">💬</div>
                        <div class="text-xs font-bold text-white">Telegram DM</div>
                        <div class="text-[9px] text-accent mt-1 opacity-60" style="color:#0088cc">Send Direct Msg</div>
                    </div>
                    ${u.is_blocked == 1 ? `
                    <div onclick="toggleUserBlock('${u.tg_id}', 1)" class="action-card btn-unblock">
                        <div class="text-2xl mb-2">🔓</div>
                        <div class="text-xs font-bold">Unblock User</div>
                        <div class="text-[9px] mt-1 opacity-60">Restore Access</div>
                    </div>
                    ` : `
                    <div onclick="toggleUserBlock('${u.tg_id}', 0)" class="action-card btn-block">
                        <div class="text-2xl mb-2">🚫</div>
                        <div class="text-xs font-bold">Block Access</div>
                        <div class="text-[9px] mt-1 opacity-60">Restrict User</div>
                    </div>
                    `}
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="history-section">
                        <div class="section-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Recent Activity
                        </div>
                        <div class="table-container" style="margin-top:12px;">
                            <table class="mini-table">
                                <tbody>
                                    ${(data.activity && data.activity.length) ? data.activity.map(a => `
                                        <tr>
                                            <td class="w-8 text-center">
                                                ${a.type === 'deposit' ? '💰' : '📦'}
                                            </td>
                                            <td>
                                                <div class="font-medium text-white text-[12px]">
                                                    ${a.type === 'deposit' ? 'Deposit' : 'Order'} #${a.id}
                                                </div>
                                                <div class="text-[10px] text-secondary opacity-60">
                                                    ${new Date(a.date).toLocaleDateString()}
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <div class="font-bold text-[11px] ${a.type === 'deposit' ? 'text-green-400' : 'text-accent'}">
                                                    ${a.type === 'deposit' ? '+' : ''}${parseFloat(a.amount).toFixed(2)} ETB
                                                </div>
                                                <span class="status-badge status-${a.status} !text-[8px] !px-1.5 !py-0">${a.status}</span>
                                            </td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="3" class="text-center py-8 text-secondary text-xs opacity-50">No activity yet</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="history-section">
                        <div class="section-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                            Security Audit
                        </div>
                        <div class="space-y-3" style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                             ${logs.length ? logs.map(l => `
                                <div class="log-item">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-[10px] font-bold text-accent uppercase">${(l.type || 'LOG').replace('_', ' ')}</span>
                                        <span class="text-[9px] text-gray-500 font-mono">${l.date ? new Date(l.date).toLocaleDateString() : 'N/A'}</span>
                                    </div>
                                    <div class="text-[12px] text-gray-300 leading-tight">${l.message || ''}</div>
                                    ${l.note ? `<div class="text-[10px] text-gray-500 italic mt-1 bg-black/20 p-1 rounded">"${l.note}"</div>` : ''}
                                </div>
                             `).join('') : '<div class="text-center py-8 text-secondary text-xs opacity-50">Clear audit history</div>'}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('modal-user-content').innerHTML = html;
        }

        async function sendUserModalDirectMessage(tgId, name) {
            const msg = prompt(`Send direct Telegram message to ${name}:`);
            if (!msg) return;
            
            try {
                const res = await fetch('api_bot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'direct_message', tg_id: tgId, message: msg })
                });
                const data = await res.json();
                if (data.success) {
                    alert("Message sent via Telegram!");
                } else {
                    alert("Error: " + data.error);
                }
            } catch (e) {
                console.error(e);
                alert("Failed to send direct message.");
            }
        }

        // --- LIVE CHAT SUPPORT ---
        let currentChatUserId = null;
        let chatPollingInterval = null;
        
        async function loadChatUsers() {
            const container = document.getElementById('chat-users-list');
            try {
                const res = await fetch('api_chat.php?action=get_users');
                const data = await res.json();
                
                if (data.success && data.users.length > 0) {
                    container.innerHTML = data.users.map(user => `
                        <div class="chat-user-item ${currentChatUserId === user.user_id ? 'active' : ''}" 
                             onclick="selectChatUser('${user.user_id}', '${user.first_name.replace(/'/g, "\\'")}', '${user.photo_url}')">
                            <img src="${user.photo_url || 'https://ui-avatars.com/api/?name=U&background=6c5ce7&color=fff'}" alt="">
                            <div class="chat-user-info">
                                <div class="chat-user-name">
                                    <span>${user.first_name}</span>
                                    ${user.unread_count > 0 ? `<span class="unread-badge">${user.unread_count}</span>` : ''}
                                </div>
                                <div class="chat-user-preview">
                                    ${user.last_message ? escapeHtml(user.last_message) : 'No messages'}
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="empty-state">No conversations yet</div>';
                }
            } catch (e) {
                console.error('Failed to load chat users:', e);
                container.innerHTML = '<div class="empty-state text-danger">Failed to load chats</div>';
            }
        }
        
        async function startUserChat(userId, name, photoUrl) {
             loadTab('chat');
             // Small delay to allow tab render
             setTimeout(() => {
                 selectChatUser(userId, name, photoUrl);
             }, 50);
         }

         async function selectChatUser(userId, name, photoUrl) {
             currentChatUserId = userId;
             
             // Mobile: Show main, hide sidebar
             if (window.innerWidth <= 768) {
                 const sidebar = document.getElementById('chat-sidebar');
                 const main = document.getElementById('chat-main');
                 sidebar.classList.add('hidden');
                 main.classList.remove('hidden');
                 // Force display block for active panel just in case
                 document.getElementById('chat-active-panel').style.display = 'flex';
                 document.getElementById('chat-no-selection').style.display = 'none';
             }
             
             // Update UI
             document.getElementById('chat-no-selection').style.display = 'none';
             document.getElementById('chat-active-panel').style.display = 'flex';
             document.getElementById('chat-user-avatar').src = photoUrl || 'https://ui-avatars.com/api/?name=U&background=6c5ce7&color=fff';
             document.getElementById('chat-user-name').textContent = name;
             document.getElementById('chat-user-id').textContent = 'ID: ' + userId;
             
             // Reload user list to update active state
             // We don't await this because if it's a new user they won't be in the list yet
             loadChatUsers();
             
             // Load messages
             await loadAdminChatMessages();
             
             // Start polling for new messages - switch to real SSE in next step if possible
             if (chatPollingInterval) clearInterval(chatPollingInterval);
             chatPollingInterval = setInterval(loadAdminChatMessages, 3000);
             
             // Focus input
             setTimeout(() => {
                 document.getElementById('admin-chat-input').focus();
             }, 100);
             
             // Setup enter key
             document.getElementById('admin-chat-input').onkeypress = (e) => {
                 if (e.key === 'Enter') sendAdminReply();
             };
         }
 
         function backToChatList() {
             const sidebar = document.getElementById('chat-sidebar');
             const main = document.getElementById('chat-main');
             
             sidebar.classList.remove('hidden');
             
             if (window.innerWidth <= 768) {
                 // On mobile, explicitly hide main to prevent overlap glitches
                 main.classList.add('hidden');
             }
         }
        
        async function loadAdminChatMessages() {
            if (!currentChatUserId) return;
            
            try {
                const res = await fetch(`api_chat.php?action=get_messages&user_id=${currentChatUserId}`);
                const data = await res.json();
                
                const container = document.getElementById('admin-chat-messages');
                if (data.success && data.messages) {
                    container.innerHTML = data.messages.map(msg => {
                        const isAdmin = msg.sender === 'admin';
                        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        return `
                            <div class="chat-bubble ${isAdmin ? 'admin' : 'user'}">
                                <div>${escapeHtml(msg.message)}</div>
                                <span class="chat-time">${time}</span>
                            </div>
                        `;
                    }).join('');
                    container.scrollTop = container.scrollHeight;
                }
            } catch (e) {
                console.error('Failed to load chat messages:', e);
            }
        }
        
        async function closeAdminChat() {
            if (!currentChatUserId) return;
            
            if (!confirm('Are you sure you want to CLOSE this chat and CLEAR all history for both you and the user? This cannot be undone.')) {
                return;
            }
            
            try {
                const res = await fetch(`api_chat.php?action=close_chat&user_id=${currentChatUserId}`);
                const data = await res.json();
                
                if (data.success) {
                    // Stop polling
                    if (chatPollingInterval) clearInterval(chatPollingInterval);
                    
                    // Reset UI
                    document.getElementById('chat-active-panel').style.display = 'none';
                    document.getElementById('chat-no-selection').style.display = 'flex';
                    currentChatUserId = null;
                    
                    // Refresh user list
                    loadChatUsers();
                    
                    alert('Chat closed and history cleared successfully.');
                } else {
                    alert('Error: ' + (data.error || 'Failed to close chat'));
                }
            } catch (e) {
                console.error('Failed to close chat:', e);
                alert('An error occurred while closing the chat.');
            }
        }
        
        async function sendAdminReply() {
            const input = document.getElementById('admin-chat-input');
            const message = input.value.trim();
            if (!message || !currentChatUserId) return;
            
            input.value = '';
            
            try {
                const res = await fetch('api_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'send_reply',
                        user_id: currentChatUserId,
                        message: message
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    await loadAdminChatMessages();
                    loadChatUsers(); // Update user list
                } else {
                    alert('Failed to send: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                console.error('Failed to send reply:', e);
                alert('Failed to send message');
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

    </script>

    <!-- AI Assistant UI -->
    <div id="ai-chat-bubble" onclick="toggleAiChat()">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
    </div>

    <div id="ai-chat-window">
        <div class="chat-header">
            <div class="status-dot"></div>
            <span>Paxyo Admin Friend</span>
            <div style="margin-left:auto; cursor:pointer;" onclick="toggleAiChat()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
        </div>
        <div class="ai-messages-container" id="ai-chat-messages">
            <div class="msg msg-ai">
                Hey boss! 👋 I'm your Paxyo assistant. I'm connected directly to your database. Ask me anything about your site!
            </div>
        </div>
        <div id="chat-typing" class="typing-indicator">Admin Friend is thinking...</div>
        <div class="ai-input-area">
            <input type="text" id="ai-input" class="chat-input" placeholder="Ask about users, revenue, orders...">
            <button class="btn btn-primary btn-sm" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; justify-content: center;" onclick="sendAiMessage()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </div>
    </div>

    <!-- User Explorer Modal -->
    <div id="user-modal-overlay" class="modal-overlay" onclick="if(event.target === this) closeUserModal()">
        <div class="modal-content" id="user-modal">
            <div class="modal-header">
                <h3>User Explorer</h3>
                <button class="close-modal-btn" onclick="closeUserModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="modal-user-content">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>

    <!-- Broadcast Report Modal -->
    <div id="broadcast-report-modal" class="modal-overlay" onclick="if(event.target === this) closeReportModal()">
        <div class="modal-content" style="max-width: 700px; width: 90%;">
            <div class="modal-header">
                <h3 class="font-bold">📢 Broadcast Delivery Report</h3>
                <button class="close-modal-btn" onclick="closeReportModal()">✕</button>
            </div>
            <div class="modal-body p-0" id="broadcast-report-content">
                <!-- Data here -->
            </div>
        </div>
    </div>
</body>
</html>
