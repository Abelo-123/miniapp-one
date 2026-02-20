# Cron Job Setup Guide

## For Windows (Local Development)

### Option 1: Windows Task Scheduler (Recommended)

**Quick Setup via PowerShell (Run as Administrator):**

```powershell
# Create task that runs every minute
$action = New-ScheduledTaskAction -Execute "d:\next\xampp\php\php.exe" -Argument "d:\next\xampp\htdocs\paxyo\cron_check_orders.php"
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
Register-ScheduledTask -TaskName "PaxyoOrderChecker" -Action $action -Trigger $trigger -Settings $settings -Description "Checks order statuses every minute"
```

**Manual Setup via GUI:**

1. Press `Win + R`, type `taskschd.msc`, press Enter
2. Click **"Create Task"** (not "Create Basic Task")
3. **General Tab:**
   - Name: `PaxyoOrderChecker`
   - Description: `Checks order statuses every minute`
   - ☑ Run whether user is logged on or not
   - ☑ Run with highest privileges

4. **Triggers Tab:**
   - Click **New**
   - Begin the task: `On a schedule`
   - Settings: `Daily`
   - Start: `Today at current time`
   - ☑ Repeat task every: `1 minute`
   - for a duration of: `Indefinitely`
   - ☑ Enabled
   - Click **OK**

5. **Actions Tab:**
   - Click **New**
   - Action: `Start a program`
   - Program/script: `d:\next\xampp\php\php.exe`
   - Add arguments: `d:\next\xampp\htdocs\paxyo\cron_check_orders.php`
   - Start in: `d:\next\xampp\htdocs\paxyo`
   - Click **OK**

6. **Conditions Tab:**
   - ☐ Uncheck "Start the task only if the computer is on AC power"
   - ☑ Check "Wake the computer to run this task"

7. **Settings Tab:**
   - ☑ Allow task to be run on demand
   - ☑ Run task as soon as possible after a scheduled start is missed
   - If the task is already running: `Do not start a new instance`

8. Click **OK** and enter your Windows password if prompted

**Verify it's running:**
```powershell
Get-ScheduledTask -TaskName "PaxyoOrderChecker"
```

**Test manually:**
```powershell
Start-ScheduledTask -TaskName "PaxyoOrderChecker"
```

**View last run result:**
```powershell
Get-ScheduledTaskInfo -TaskName "PaxyoOrderChecker"
```

**Stop/Delete the task:**
```powershell
Unregister-ScheduledTask -TaskName "PaxyoOrderChecker" -Confirm:$false
```

### Option 2: Simple Batch Script (Alternative)

Create `run_cron.bat`:
```batch
@echo off
:loop
d:\next\xampp\php\php.exe d:\next\xampp\htdocs\paxyo\cron_check_orders.php
timeout /t 60 /nobreak
goto loop
```

Then run it in background:
```batch
start /min run_cron.bat
```

---

## For cPanel (Production)

### Step 1: Login to cPanel

Go to: `https://yourdomain.com:2083`

### Step 2: Find Cron Jobs

Search for **"Cron Jobs"** in cPanel search bar, or:
- Advanced → Cron Jobs

### Step 3: Add Cron Job

**Common Settings:**
- Minute: `*/1` (every minute)
- Hour: `*`
- Day: `*`
- Month: `*`
- Weekday: `*`

**Command:**
```bash
/usr/bin/php /home/yourusername/public_html/cron_check_orders.php
```

**Find your correct path:**
1. In cPanel File Manager, navigate to your site
2. Copy the path shown at the top (e.g., `/home/yourusername/public_html`)

**Alternative commands (try if first doesn't work):**
```bash
# Option 1: Full path
/usr/local/bin/php /home/yourusername/public_html/cron_check_orders.php

# Option 2: Using php-cli
/usr/bin/php-cli /home/yourusername/public_html/cron_check_orders.php

# Option 3: Using EA-PHP (if on CloudLinux)
/opt/cpanel/ea-php81/root/usr/bin/php /home/yourusername/public_html/cron_check_orders.php

# Option 4: With output to log file
/usr/bin/php /home/yourusername/public_html/cron_check_orders.php >> /home/yourusername/cron.log 2>&1
```

### Step 4: Save

Click **"Add New Cron Job"**

### Step 5: Verify

**Check cron email:**
- cPanel will send you an email every time the cron runs
- Check your cPanel email or the email associated with your account

**Disable cron emails (optional):**
Add this at the top of the command:
```bash
/usr/bin/php /home/yourusername/public_html/cron_check_orders.php > /dev/null 2>&1
```

**View cron log:**
Create a log file version:
```bash
/usr/bin/php /home/yourusername/public_html/cron_check_orders.php >> /home/yourusername/logs/cron.log 2>&1
```

Then view logs in File Manager: `~/logs/cron.log`

---

## Testing the Cron Job

### Test Manually (Both Windows & cPanel)

**Windows:**
```bash
d:\next\xampp\php\php.exe d:\next\xampp\htdocs\paxyo\cron_check_orders.php
```

**cPanel (via SSH or Terminal in cPanel):**
```bash
php /home/yourusername/public_html/cron_check_orders.php
```

**Expected Output:**
```
[2025-12-14 19:30:00] Starting order status check...
  User 111: 2 orders updated
[2025-12-14 19:30:02] Complete: Checked 5 users, 2 orders updated
```

### Verify It's Working

1. **Check database:**
   - Open phpMyAdmin
   - Check `orders` table
   - Look at `updated_at` column - should update every minute

2. **Check error logs:**
   - Windows: `d:\next\xampp\apache\logs\error.log`
   - cPanel: `~/public_html/error_log` or via cPanel Error Log viewer

3. **Look for refund messages:**
   Search error.log for:
   ```
   REFUND: $X.XX for Order #123 (Canceled)
   ```

---

## Troubleshooting

### Windows Issues

**"Access Denied" error:**
- Run PowerShell as Administrator
- Or manually create task with "Run with highest privileges"

**Task not running:**
- Check Task Scheduler → Task Status
- Right-click task → Run to test manually
- Check "Last Run Result" (should be 0x0 for success)

**PHP not found:**
- Verify PHP path: `where php` in CMD
- Update path in task to match your XAMPP installation

### cPanel Issues

**"Command not found":**
- Try different PHP paths (see alternatives above)
- Contact your host to ask for correct PHP path

**"Permission denied":**
- Check file permissions: `chmod 644 cron_check_orders.php`
- Ensure file is in correct directory

**Cron not running:**
- Check cron email for errors
- Verify path is absolute (starts with `/home/`)
- Test command manually via SSH

**Too many emails:**
- Add `> /dev/null 2>&1` to suppress output
- Or set up email forwarding in cPanel

---

## Advanced: Different Frequencies

**Every 30 seconds (if supported):**
```
* * * * * /usr/bin/php /path/to/cron_check_orders.php
* * * * * sleep 30; /usr/bin/php /path/to/cron_check_orders.php
```

**Every 2 minutes:**
```
*/2 * * * * /usr/bin/php /path/to/cron_check_orders.php
```

**Every 5 minutes:**
```
*/5 * * * * /usr/bin/php /path/to/cron_check_orders.php
```

**Only during business hours (9 AM - 5 PM):**
```
* 9-17 * * * /usr/bin/php /path/to/cron_check_orders.php
```

---

## Monitoring

### Create a Status Page

Create `cron_status.php`:
```php
<?php
$last_run_file = sys_get_temp_dir() . '/paxyo_cron_last_run.txt';
if (file_exists($last_run_file)) {
    $last_run = file_get_contents($last_run_file);
    $seconds_ago = time() - intval($last_run);
    echo "Last run: " . $seconds_ago . " seconds ago\n";
    echo "Status: " . ($seconds_ago < 120 ? "✅ OK" : "❌ NOT RUNNING") . "\n";
} else {
    echo "❌ Cron has never run\n";
}
?>
```

Update `cron_check_orders.php` to track last run:
```php
// Add at the end of the file
file_put_contents(sys_get_temp_dir() . '/paxyo_cron_last_run.txt', time());
```

---

## Quick Reference

| Platform | Command |
|----------|---------|
| **Windows PowerShell** | `Register-ScheduledTask -TaskName "PaxyoOrderChecker" ...` |
| **Windows Manual Test** | `d:\next\xampp\php\php.exe d:\next\xampp\htdocs\paxyo\cron_check_orders.php` |
| **cPanel** | `*/1 * * * * /usr/bin/php /home/user/public_html/cron_check_orders.php` |
| **cPanel Test** | `php /home/user/public_html/cron_check_orders.php` |

---

## Next Steps

After setting up cron:

1. ✅ Test it manually first
2. ✅ Wait 1-2 minutes and check if it auto-runs
3. ✅ Place a test order
4. ✅ Cancel it from GodOfPanel
5. ✅ Within 60 seconds, verify refund appears
6. ✅ Check browser console for SSE updates

Your real-time system is now complete! 🎉
