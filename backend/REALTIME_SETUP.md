# Real-time Order System - Final Setup (No Webhooks Needed)

## Why This Approach?

GodOfPanel **does not support webhooks**, so we use a smart hybrid approach:
- ✅ **Server-side cron** checks API every 30-60 seconds (not every user)
- ✅ **WebSocket** pushes updates to users instantly
- ✅ **Zero browser polling** - users get real-time updates
- ✅ **Efficient** - Only 1 API call per minute instead of hundreds

## Setup Steps

### 1. Start WebSocket Server

```bash
# Open terminal and run:
cd d:\next\xampp\htdocs\paxyo
d:\next\xampp\php\php.exe websocket_server.php
```

Keep this running. You should see:
```
WebSocket server started on 0.0.0.0:8080
```

### 2. Setup Windows Task Scheduler (Cron Alternative)

Since you're on Windows, we'll use Task Scheduler instead of cron:

**Option A: Quick Setup (PowerShell)**
```powershell
# Run as Administrator
$action = New-ScheduledTaskAction -Execute "d:\next\xampp\php\php.exe" -Argument "d:\next\xampp\htdocs\paxyo\cron_check_orders.php"
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Seconds 30) -RepetitionDuration ([TimeSpan]::MaxValue)
Register-ScheduledTask -TaskName "PaxyoOrderChecker" -Action $action -Trigger $trigger -Description "Checks order statuses every 30 seconds"
```

**Option B: Manual Setup (GUI)**
1. Open Task Scheduler (search "Task Scheduler" in Windows)
2. Click "Create Task"
3. **General Tab:**
   - Name: `PaxyoOrderChecker`
   - Run whether user is logged on or not
4. **Triggers Tab:**
   - New → Daily
   - Repeat task every: `1 minute` (or 30 seconds if available)
   - Duration: `Indefinitely`
5. **Actions Tab:**
   - New → Start a program
   - Program: `d:\next\xampp\php\php.exe`
   - Arguments: `d:\next\xampp\htdocs\paxyo\cron_check_orders.php`
   - Start in: `d:\next\xampp\htdocs\paxyo`
6. Click OK

### 3. Test the System

**Test the cron job manually:**
```bash
d:\next\xampp\php\php.exe d:\next\xampp\htdocs\paxyo\cron_check_orders.php
```

You should see output like:
```
[2025-12-14 19:15:00] Starting order status check...
  User 111: 2 orders updated
[2025-12-14 19:15:02] Complete: Checked 5 users, 2 orders updated
```

**Test WebSocket connection:**
1. Open your app in browser
2. Open browser console (F12)
3. You should see: `WebSocket connected`
4. Place a test order and cancel it from GodOfPanel
5. Within 30-60 seconds, you'll see the refund notification

### 4. Update Frontend WebSocket URL (if needed)

In `smm.php` around line 650, update if deploying to production:
```javascript
const wsUrl = 'ws://localhost:8080'; // For local
// const wsUrl = 'ws://yoursite.com:8080'; // For production
```

## How It Works

```
Every 30 seconds:
  ↓
cron_check_orders.php runs
  ↓
Checks ALL active orders via GodOfPanel API
  ↓
Detects status changes & processes refunds
  ↓
Sends update to WebSocket server
  ↓
WebSocket pushes to connected users INSTANTLY
  ↓
User sees notification + balance update
```

## Refund Logic

### Canceled Orders
- **Full refund** of charge amount
- Logged: `WEBHOOK REFUND: $X.XX for Order #123 (Canceled)`

### Partial Orders
- **Smart refund**: `charge × (remains / quantity)`
- Example: $10 order, 300/1000 delivered → $7 refund
- Logged: `WEBHOOK REFUND: $X.XX for Order #123 (Partial, 300 remains)`

## Performance

- **API Calls**: 1 per minute (vs hundreds with browser polling)
- **Update Latency**: 0-60 seconds (configurable)
- **Server Load**: Minimal (single PHP process)
- **User Experience**: Real-time (WebSocket is instant)

## Monitoring

**Check if cron is running:**
```powershell
Get-ScheduledTask -TaskName "PaxyoOrderChecker"
```

**View cron logs:**
Check `d:\next\xampp\apache\logs\error.log` for refund messages

**Check WebSocket connections:**
Look at the terminal where `websocket_server.php` is running

## Production Deployment

### Run WebSocket as Windows Service

Install NSSM (Non-Sucking Service Manager):
```bash
# Download from nssm.cc
nssm install PaxyoWebSocket "d:\next\xampp\php\php.exe" "d:\next\xampp\htdocs\paxyo\websocket_server.php"
nssm start PaxyoWebSocket
```

### Adjust Cron Frequency

For faster updates, change Task Scheduler to run every 15-30 seconds.
For less API load, use 60-120 seconds.

## Troubleshooting

**Orders not updating:**
- Check if Task Scheduler task is running
- Run cron manually to see errors
- Check error.log for API issues

**WebSocket not connecting:**
- Verify server is running: `netstat -an | findstr 8080`
- Check firewall allows port 8080
- Look for errors in WebSocket terminal

**Refunds not working:**
- Check error.log for "REFUND" messages
- Verify order status in database
- Test cron job manually

## Advantages Over Pure Polling

✅ **1 API call/min** instead of 100s
✅ **Instant UI updates** via WebSocket
✅ **Scalable** - works with 1000s of users
✅ **Battery efficient** - no browser polling
✅ **Real-time feel** - updates within 30-60s
