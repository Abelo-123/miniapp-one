# Complete cPanel Production Setup Guide

## 🚀 SUPER SMART SYSTEM

Your system now:
- ✅ **Cron completely stops** when no active orders (zero output, zero API calls)
- ✅ **SSE pauses heartbeats** when idle (sends "idle" every 10s instead of heartbeat every 2s)
- ✅ **Auto-resumes** when new orders are placed
- ✅ **Maximum efficiency** - uses almost zero resources when idle

---

## 📦 Step-by-Step cPanel Deployment

### STEP 1: Prepare Database

**1.1 Create MySQL Database in cPanel**
- Go to: cPanel → MySQL® Databases
- Create database: `youruser_paxyo`
- Create user: `youruser_paxyodb`
- Set strong password
- Add user to database with ALL PRIVILEGES

**1.2 Update db.php**
```php
<?php
$db_host = 'localhost';
$db_user = 'youruser_paxyodb';      // ← Your cPanel MySQL username
$db_pass = 'your_strong_password';   // ← Your password
$db_name = 'youruser_paxyo';         // ← Your database name

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
```

**1.3 Import Database Tables**
- Go to: cPanel → phpMyAdmin
- Select your database (`youruser_paxyo`)
- Click "SQL" tab
- Run `setup_orders_table.php` once via browser: `https://yoursite.com/setup_orders_table.php`
- Or import any `.sql` files you have

---

### STEP 2: Upload Files

**2.1 Via cPanel File Manager**
- Go to: cPanel → File Manager
- Navigate to `public_html`
- Click "Upload"
- Upload ALL your project files:
  - ✅ `smm.php`
  - ✅ `smm_styles.css`
  - ✅ `db.php` (with updated credentials)
  - ✅ `cron_check_orders.php`
  - ✅ `order_manager.php`
  - ✅ `realtime_stream.php`
  - ✅ `process_order.php`
  - ✅ `get_orders.php`
  - ✅ `get_alerts.php`
  - ✅ All other PHP files

**2.2 Set File Permissions**
- Select all PHP files
- Right-click → Change Permissions
- Set to: `644` (rw-r--r--)
- For directories: `755` (rwxr-xr-x)

---

### STEP 3: Setup Cron Jobs (6 Jobs for 10-Second Intervals)

**3.1 Find Your Absolute Path**
- In File Manager, navigate to your site root
- Look at the path at the top (e.g., `/home/youruser/public_html`)
- Copy this path - you'll need it!

**3.2 Add Cron Jobs**
- Go to: cPanel → Cron Jobs
- Scroll to "Add New Cron Job"

**Add these 6 jobs** (one at a time):

**Job 1:**
```
Minute: *
Hour: *
Day: *
Month: *
Weekday: *
Command: /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
```

**Job 2:**
```
Minute: *
Hour: *
Day: *
Month: *
Weekday: *
Command: sleep 10; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
```

**Job 3:**
```
Minute: *
Hour: *
Day: *
Month: *
Weekday: *
Command: sleep 20; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
```

**Job 4:**
```
Minute: *
Hour: *
Day: *
Month: *
Weekday: *
Command: sleep 30; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
```

**Job 5:**
```
Minute: *
Hour: *
Day: *
Month: *
Weekday: *
Command: sleep 40; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
```

**Job 6:**
```
Minute: *
Hour: *
Day: *
Month: *
Weekday: *
Command: sleep 50; /usr/bin/php /home/youruser/public_html/cron_check_orders.php > /dev/null 2>&1
```

**IMPORTANT:** Replace `/home/youruser/public_html/` with YOUR actual path!

**Alternative PHP paths** (if `/usr/bin/php` doesn't work):
- `/usr/local/bin/php`
- `/opt/cpanel/ea-php81/root/usr/bin/php`
- `/usr/bin/php-cli`

Ask your hosting provider if unsure.

---

### STEP 4: Test Everything

**4.1 Test Database Connection**
Visit: `https://yoursite.com/debug_schema_clean.php`

You should see your database structure.

**4.2 Test Cron Manually**
- Go to: cPanel → Terminal (or SSH)
- Run:
```bash
php /home/youruser/public_html/cron_check_orders.php
```

**Expected output when NO orders:**
```
(no output - exits silently)
```

**Expected output when orders exist:**
```
[2025-12-14 19:50:00] Checking 3 active orders...
  ✓ User 111: 2 orders updated
[2025-12-14 19:50:02] Complete: 2 updates
```

**4.3 Test SSE Stream**
Visit in browser: `https://yoursite.com/realtime_stream.php`

You should see streaming data:
```
data: {"type":"idle","message":"No active orders"}

data: {"type":"heartbeat","timestamp":1702579200}
```

**4.4 Test Full System**
1. Open your site: `https://yoursite.com/smm.php`
2. Open browser console (F12)
3. You should see:
   ```
   🔴 Starting real-time stream (SSE)
   ✅ Real-time connection established
   ```
4. Place a test order
5. Cancel it from GodOfPanel
6. Within 10-12 seconds, you'll see refund notification

---

### STEP 5: Verify Cron is Running

**5.1 Check Cron Email**
- cPanel sends email when cron runs
- Check your cPanel email inbox
- You should see emails (or silence if no orders)

**5.2 Check Cron Status**
- Go to: cPanel → Cron Jobs
- Look at "Current Cron Jobs" section
- All 6 jobs should be listed

**5.3 Monitor Logs**
Create a log version (optional):
```bash
/usr/bin/php /home/youruser/public_html/cron_check_orders.php >> /home/youruser/logs/cron.log 2>&1
```

Then view: `~/logs/cron.log` in File Manager

---

## 🎯 How It Works in Production

### When NO Active Orders:
```
Cron runs every 10s
   ↓
Checks database: 0 active orders
   ↓
Deletes flag file
   ↓
Exits silently (no output, no API call)
   ↓
SSE detects no flag
   ↓
Sends "idle" message every 10s (instead of heartbeat every 2s)
```

### When Active Orders Exist:
```
User places order
   ↓
Cron runs (next 10s cycle)
   ↓
Finds active order
   ↓
Creates flag file
   ↓
Calls GodOfPanel API
   ↓
Updates database
   ↓
SSE detects flag + DB change
   ↓
Sends update to user within 2s
   ↓
User sees notification instantly
```

---

## 📊 Resource Usage

| Scenario | Cron Output | API Calls | SSE Messages |
|----------|-------------|-----------|--------------|
| **No orders** | Silent | 0/min | 1 idle/10s |
| **Active orders** | Logs updates | 6/min | Updates + heartbeats |

**Result:** Near-zero resource usage when idle, efficient when active!

---

## 🔧 Troubleshooting

### Cron Not Running
**Check:**
- Verify PHP path: `which php` in Terminal
- Check file permissions: Should be 644
- Look for errors in cPanel Error Log

**Fix:**
- Try alternative PHP paths (see Step 3.2)
- Contact hosting support for correct PHP path

### Database Connection Failed
**Check:**
- Verify credentials in `db.php`
- Ensure user has ALL PRIVILEGES
- Check database name is correct

**Fix:**
- Re-create database user
- Grant all privileges again
- Test with `debug_schema_clean.php`

### SSE Not Connecting
**Check:**
- Visit `realtime_stream.php` directly
- Check browser console for errors
- Verify PHP version is 7.4+

**Fix:**
- Check if `session_start()` is working
- Ensure no output before headers
- Check error_log for PHP errors

### Refunds Not Working
**Check:**
- Look for "REFUND" in error logs
- Verify `order_manager.php` is uploaded
- Check API key is correct

**Fix:**
- Test cron manually
- Check database `orders` table for status updates
- Verify balance column exists in `auth` table

---

## ✅ Final Checklist

- [ ] Database created and credentials updated in `db.php`
- [ ] All files uploaded to `public_html`
- [ ] File permissions set to 644
- [ ] 6 cron jobs added (every 10 seconds)
- [ ] Cron tested manually - works
- [ ] SSE stream tested - connects
- [ ] Full system tested - order placed and refunded
- [ ] Browser console shows real-time connection
- [ ] No errors in cPanel Error Log

---

## 🎉 You're Live!

Your production system is now running with:
- ✅ **Super smart cron** - stops when idle
- ✅ **Efficient SSE** - pauses heartbeats when no orders
- ✅ **Real-time updates** - 2-12 second latency
- ✅ **Automatic refunds** - instant when order canceled
- ✅ **Zero waste** - minimal resources when idle

**Need help?** Check error logs:
- cPanel → Errors
- Or: `~/public_html/error_log`

Look for lines with "REFUND" to verify refunds are working!
