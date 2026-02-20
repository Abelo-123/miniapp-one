# Quick Setup: 10-Second Cron for Real-time Updates

## ✅ What Changed

1. **Cron now runs every 10 seconds** (instead of 60s) for faster updates
2. **Smart optimization**: Skips API calls if no active orders (saves resources)
3. **cPanel ready**: Clear instructions included in the code

---

## Windows Setup (Local)

### Option 1: Quick Start with Batch File

**Just double-click:** `run_cron_10s.bat`

This will run the cron every 10 seconds. Keep the window open.

### Option 2: Windows Task Scheduler (Background)

**PowerShell (Run as Administrator):**
```powershell
# Delete old task if exists
Unregister-ScheduledTask -TaskName "PaxyoOrderChecker" -Confirm:$false -ErrorAction SilentlyContinue

# Create new task (every 10 seconds)
$action = New-ScheduledTaskAction -Execute "d:\next\xampp\php\php.exe" -Argument "d:\next\xampp\htdocs\paxyo\cron_check_orders.php"
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Seconds 10) -RepetitionDuration ([TimeSpan]::MaxValue)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
Register-ScheduledTask -TaskName "PaxyoOrderChecker" -Action $action -Trigger $trigger -Settings $settings -Description "Checks order statuses every 10 seconds" -Force
```

**Verify it's running:**
```powershell
Get-ScheduledTask -TaskName "PaxyoOrderChecker"
```

---

## cPanel Setup (Production)

### What to Change for cPanel Deployment

**✅ GOOD NEWS: Almost nothing!**

The cron file is already cPanel-ready. Just update:

1. **Database credentials** in `db.php`:
   ```php
   $db_host = 'localhost';
   $db_user = 'youruser_paxyodb';  // Your cPanel DB user
   $db_pass = 'your_password';      // Your DB password
   $db_name = 'youruser_paxyo';     // Your DB name
   ```

2. **That's it!** No other changes needed.

### cPanel Cron Jobs Setup

**In cPanel → Cron Jobs, add these 6 jobs:**

```bash
# Job 1: Run at 0 seconds
* * * * * /usr/bin/php /home/yourusername/public_html/cron_check_orders.php

# Job 2: Run at 10 seconds
* * * * * sleep 10; /usr/bin/php /home/paxyocom/public_html/net/cron_check_orders.php

# Job 3: Run at 20 seconds
* * * * * sleep 20; /usr/bin/php /home/paxyocom/public_html/net/cron_check_orders.php

# Job 4: Run at 30 seconds
* * * * * sleep 30; /usr/bin/php /home/paxyocom/public_html/net/cron_check_orders.php

# Job 5: Run at 40 seconds
* * * * * sleep 40; /usr/bin/php /home/yourusername/public_html/cron_check_orders.php

# Job 6: Run at 50 seconds
* * * * * sleep 50; /usr/bin/php /home/paxyocom/public_html/net/cron_check_orders.php
```

**Replace `/home/yourusername/public_html/` with your actual path!**

To find your path:
1. Open cPanel File Manager
2. Navigate to your site root
3. Copy the path shown at the top

### Alternative PHP Paths (if above doesn't work)

Try these if the first command fails:

```bash
# CloudLinux EA-PHP
/opt/cpanel/ea-php81/root/usr/bin/php /home/yourusername/public_html/cron_check_orders.php

# PHP-CLI
/usr/bin/php-cli /home/yourusername/public_html/cron_check_orders.php

# Local PHP
/usr/local/bin/php /home/yourusername/public_html/cron_check_orders.php
```

### Suppress Email Notifications

Add `> /dev/null 2>&1` to each command:

```bash
* * * * * /usr/bin/php /home/yourusername/public_html/cron_check_orders.php > /dev/null 2>&1
* * * * * sleep 10; /usr/bin/php /home/yourusername/public_html/cron_check_orders.php > /dev/null 2>&1
# ... etc for all 6 jobs
```

---

## How It Works Now

### Smart Optimization

```
Cron runs every 10 seconds
   ↓
Check: Are there any pending/processing orders?
   ↓
NO → Skip API call (saves resources)
YES → Call API and update database
   ↓
SSE detects DB change within 2 seconds
   ↓
User sees update (total: 2-12 seconds)
```

### Example Output

**When there are active orders:**
```
[2025-12-14 19:40:00] Starting order status check...
  Found 3 active orders to check
  User 111: 2 orders updated
[2025-12-14 19:40:02] Complete: Checked 1 users, 2 orders updated
```

**When there are NO active orders:**
```
[2025-12-14 19:40:10] Starting order status check...
[2025-12-14 19:40:10] No active orders to check. Skipping API call.
```

---

## Testing

### Test Manually

**Windows:**
```bash
d:\next\xampp\php\php.exe d:\next\xampp\htdocs\paxyo\cron_check_orders.php
```

**cPanel (via SSH or Terminal):**
```bash
php /home/yourusername/public_html/cron_check_orders.php
```

### Full System Test

1. **Start the cron** (batch file or task scheduler)
2. **Open your app** in browser
3. **Open browser console** (F12)
4. You should see:
   ```
   🔴 Starting real-time stream (SSE)
   ✅ Real-time connection established
   💓 Heartbeat
   ```
5. **Place a test order**
6. **Cancel it** from GodOfPanel dashboard
7. **Within 10-12 seconds** you should see:
   - Toast notification
   - Balance update
   - Order status change in history

---

## Performance Impact

### API Calls Comparison

**Old way (browser polling):**
- 100 users × 1 call/minute = 100 API calls/minute ❌

**New way (cron):**
- 1 call every 10 seconds = 6 API calls/minute ✅
- But only if there are active orders!
- If no orders: 0 API calls ✅✅

### Resource Usage

- **With active orders**: ~6 API calls/minute
- **Without active orders**: 0 API calls (just DB check)
- **SSE connections**: Minimal (just listening)

---

## Monitoring

### Check if Cron is Running

**Windows:**
```powershell
Get-ScheduledTask -TaskName "PaxyoOrderChecker" | Get-ScheduledTaskInfo
```

**cPanel:**
- Check cron email
- Or view logs: `~/public_html/cron.log` (if you set up logging)

### View Logs

**Windows:**
Check: `d:\next\xampp\apache\logs\error.log`

Look for:
```
REFUND: $X.XX for Order #123 (Canceled)
```

**cPanel:**
Check: `~/public_html/error_log` or via cPanel Error Log viewer

---

## Summary: What to Change for cPanel

| Item | Action |
|------|--------|
| **db.php** | ✏️ Update database credentials |
| **cron_check_orders.php** | ✅ No changes needed! |
| **order_manager.php** | ✅ No changes needed! |
| **realtime_stream.php** | ✅ No changes needed! |
| **Cron Jobs** | ➕ Add 6 cron jobs (see above) |

That's it! The system is designed to work on both local and cPanel without code changes.

---

## Quick Reference

| Platform | Frequency | Command |
|----------|-----------|---------|
| **Windows (Quick)** | Every 10s | Double-click `run_cron_10s.bat` |
| **Windows (Background)** | Every 10s | Use PowerShell command above |
| **cPanel** | Every 10s | Add 6 cron jobs with `sleep` |

Your real-time system now updates **6x faster** (10s vs 60s) and is **super efficient** (skips API when no active orders)! 🚀
