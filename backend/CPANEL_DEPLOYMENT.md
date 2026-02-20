# cPanel Deployment Guide - Real-time Order System

## 📦 Files to Upload

Upload these files to your cPanel public_html directory:

### Core Files:
- `smm.php` - Main application (updated with auto-detection)
- `smm_styles.css` - Styles
- `db.php` - Database connection
- `order_manager.php` - Order sync logic
- `realtime_updates_cpanel.php` - SSE endpoint (cPanel compatible)
- `cron_check_orders.php` - Background order checker
- `get_orders.php` - Fetch order history
- `check_order_status.php` - Manual status check
- `process_order.php` - Place new orders
- All other existing PHP files

## 🗄️ Database Setup

1. **Create Database** in cPanel → MySQL Databases
   - Database name: `youruser_paxyo`
   - Create user and grant all privileges

2. **Update db.php** with cPanel credentials:
```php
$db_host = 'localhost';
$db_user = 'youruser_paxyodb';  // Your cPanel DB user
$db_pass = 'your_password';      // Your DB password
$db_name = 'youruser_paxyo';     // Your DB name
```

3. **Import Tables** via phpMyAdmin:
   - Run `setup_orders_table.php` once
   - Import any .sql files (orders_table.sql, etc.)

## ⚙️ Setup Cron Job

### In cPanel → Cron Jobs:

**Add New Cron Job:**
- **Minute:** `*/1` (every minute)
- **Hour:** `*`
- **Day:** `*`
- **Month:** `*`
- **Weekday:** `*`
- **Command:**
```bash
/usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
```

Replace `/home/youruser/public_html/` with your actual path (find it in cPanel File Manager).

### Alternative: Every 30 seconds (if supported)
Some cPanel hosts allow:
```bash
* * * * * /usr/bin/php /home/youruser/public_html/cron_check_orders.php
* * * * * sleep 30; /usr/bin/php /home/youruser/public_html/cron_check_orders.php
```

## 🔧 Configuration

### 1. Update API Key
In `order_manager.php` and `process_order.php`, update:
```php
$apiKey = 'your_godofpanel_api_key';
```

### 2. Session Settings
The code already has cPanel-compatible session settings:
```php
session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
```

### 3. File Permissions
Set these permissions via cPanel File Manager:
- PHP files: `644`
- Directories: `755`
- `cache/` directory: `755` (create if needed)

## 🚀 How It Works on cPanel

```
Every 60 seconds:
  ↓
Cron job runs cron_check_orders.php
  ↓
Checks all active orders via API
  ↓
Updates database + processes refunds
  ↓
Users' browsers detect change via SSE
  ↓
UI updates within 2-60 seconds
```

### Why SSE Instead of WebSocket?
- ✅ **Works on shared hosting** (cPanel doesn't allow WebSocket servers)
- ✅ **No special ports** needed
- ✅ **Auto-reconnects** if connection drops
- ✅ **Still feels real-time** (2-second polling from DB, not API)

## 🧪 Testing

### 1. Test Cron Job Manually
SSH into your server (or use cPanel Terminal):
```bash
php /home/youruser/public_html/cron_check_orders.php
```

You should see:
```
[2025-12-14 19:20:00] Starting order status check...
  User 111: 2 orders updated
[2025-12-14 19:20:02] Complete: Checked 5 users, 2 orders updated
```

### 2. Test SSE Endpoint
Visit in browser:
```
https://yoursite.com/realtime_updates_cpanel.php
```

You should see streaming data like:
```
data: {"type":"heartbeat","timestamp":1702579200}

data: {"type":"update","orders":[...],"balance":10.50}
```

### 3. Test Full Flow
1. Open your app
2. Open browser console (F12)
3. You should see: `Using SSE for real-time updates (cPanel compatible)`
4. Place an order
5. Cancel it from GodOfPanel
6. Within 60 seconds, you'll see refund notification

## 📊 Monitoring

### Check Cron Logs
In cPanel → Cron Jobs → View Cron Email
Or check: `/var/log/cron` (if you have SSH access)

### Check PHP Error Log
cPanel → Metrics → Errors
Look for lines with "REFUND" to verify refunds are working

### Check Database
phpMyAdmin → `orders` table
- Verify `status` column updates
- Check `updated_at` timestamps

## 🔒 Security

### 1. Protect Cron Script
Add to top of `cron_check_orders.php`:
```php
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}
```

### 2. Rate Limiting (Optional)
Add to `realtime_updates_cpanel.php`:
```php
// Limit connections per IP
$ip = $_SERVER['REMOTE_ADDR'];
$lockfile = sys_get_temp_dir() . "/sse_lock_$ip";
if (file_exists($lockfile) && (time() - filemtime($lockfile)) < 5) {
    die('Too many connections');
}
touch($lockfile);
```

## ⚡ Performance Tips

### 1. Enable OPcache
In cPanel → PHP Settings → Enable OPcache

### 2. Use Memcached (if available)
Cache service data to reduce DB queries

### 3. Optimize Cron Frequency
- High traffic: Every 30 seconds
- Medium traffic: Every 60 seconds  
- Low traffic: Every 2 minutes

### 4. Index Database
Already done in `setup_orders_table.php`:
```sql
CREATE INDEX idx_user_status ON orders(user_id, status);
CREATE INDEX idx_api_order ON orders(api_order_id);
```

## 🐛 Troubleshooting

**SSE not connecting:**
- Check if `realtime_updates_cpanel.php` is accessible
- Verify PHP version is 7.4+ (cPanel → PHP Selector)
- Check error logs for PHP errors

**Cron not running:**
- Verify cron command path is correct
- Check cron email for errors
- Test script manually via SSH/Terminal

**Refunds not working:**
- Check error.log for "REFUND" messages
- Verify `order_manager.php` is uploaded
- Test `cron_check_orders.php` manually

**Orders not updating:**
- Verify cron is running (check last run time)
- Check API key is correct
- Verify database connection

## 📈 Scaling

If you outgrow shared hosting:
1. Upgrade to VPS
2. Install Node.js WebSocket server
3. System will auto-detect and use WebSocket
4. Much better performance (instant updates)

## ✅ Checklist

- [ ] Upload all PHP files
- [ ] Update `db.php` with cPanel credentials
- [ ] Run `setup_orders_table.php` once
- [ ] Create cron job (every 1 minute)
- [ ] Update API key in `order_manager.php`
- [ ] Test cron manually
- [ ] Test SSE endpoint in browser
- [ ] Place test order and verify refund

## 🎉 You're Done!

Your real-time order system is now running on cPanel with:
- ✅ Automatic refunds for canceled/partial orders
- ✅ Real-time UI updates (within 60 seconds)
- ✅ No browser polling (SSE handles it)
- ✅ Efficient (1 API call per minute for all users)
- ✅ Works on shared hosting
