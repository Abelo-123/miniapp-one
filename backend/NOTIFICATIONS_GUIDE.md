# Real-time Notification System - Complete Guide

## ✅ Already Working!

Your notification system is **already fully functional** and works exactly like the order history system:

- ✅ **Real-time via SSE** - No polling
- ✅ **Smart pausing** - Reduces activity when idle
- ✅ **Instant updates** - Notifications appear within 2 seconds
- ✅ **Auto-reconnects** - Handles connection drops

## 🔄 How It Works

### Backend (realtime_stream.php)

The same SSE stream that handles orders also handles notifications:

```php
// Monitors alerts hash
$alerts_hash = md5(GROUP_CONCAT(id|is_read))

// When hash changes:
if ($alerts_hash !== $last_alerts_hash) {
    // Send full alerts list to frontend
    echo "data: {'type':'update','changes':['alerts'],...}";
}
```

### Frontend (smm.php)

```javascript
handleAlertsUpdate(alerts, unread_count) {
    this.alerts = alerts;
    
    // Update notification dot
    if (unread_count > 0) {
        this.elements.alertDot.classList.remove('hidden');
    } else {
        this.elements.alertDot.classList.add('hidden');
    }
    
    // If alert modal is open, refresh it
    if (this.elements.alertModal.classList.contains('visible')) {
        this.showAlertModal();
    }
}
```

## 📊 What Gets Updated in Real-time

1. **New Notifications** - Appear instantly
2. **Notification Dot** - Shows/hides based on unread count
3. **Alert Modal** - Auto-refreshes if open
4. **Read Status** - Updates when you view notifications

## 🧪 Test It

### Method 1: Using Test Script

```bash
# Send a test notification
d:\next\xampp\php\php.exe send_test_notification.php
```

**What happens:**
1. Script inserts notification into database
2. SSE detects change within 2 seconds
3. Notification appears in your app
4. Red dot appears on bell icon

### Method 2: Manual Database Insert

```sql
INSERT INTO user_alerts (user_id, message, is_read, created_at) 
VALUES (111, 'Test notification!', 0, NOW());
```

### Method 3: Via Admin Panel

If you have an admin panel, create a form to send notifications:

```php
// admin_send_notification.php
$user_id = $_POST['user_id'];
$message = $_POST['message'];

$stmt = $conn->prepare("INSERT INTO user_alerts (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
$stmt->bind_param("is", $user_id, $message);
$stmt->execute();
```

## 📱 User Experience

### When Notification Arrives:

1. **Red dot appears** on bell icon (top right)
2. **Click bell** to open notifications
3. **Notifications auto-mark as read** when modal opens
4. **Red dot disappears** after viewing

### Real-time Flow:

```
Admin sends notification
   ↓
Database INSERT
   ↓
SSE detects change (within 2s)
   ↓
Frontend receives update
   ↓
Red dot appears
   ↓
User clicks bell
   ↓
Modal opens with new notification
   ↓
Auto-marks as read
   ↓
Red dot disappears
```

## 🎯 Smart Features

### 1. Batching
If multiple notifications arrive at once, they're all sent in a single SSE message.

### 2. Deduplication
The hash system prevents duplicate updates.

### 3. Idle Mode
When no alerts change, SSE sends idle heartbeats instead of checking constantly.

### 4. Auto-Refresh
If the alert modal is open when a new notification arrives, it auto-refreshes.

## 🔧 For Admins: Sending Notifications

### Bulk Notification Script

Create `admin/send_bulk_notification.php`:

```php
<?php
include '../db.php';

// Send to all users
$message = "System maintenance scheduled for tonight at 2 AM";

$conn->query("INSERT INTO user_alerts (user_id, message, is_read, created_at) 
              SELECT tg_id, '$message', 0, NOW() FROM auth");

echo "Notification sent to all users!";
?>
```

### Targeted Notification

```php
// Send to specific user
$user_id = 111;
$message = "Your order #123 has been completed!";

$stmt = $conn->prepare("INSERT INTO user_alerts (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
$stmt->bind_param("is", $user_id, $message);
$stmt->execute();
```

## 📊 Database Schema

```sql
CREATE TABLE user_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);
```

## 🚀 Performance

| Metric | Value |
|--------|-------|
| **Detection Time** | < 2 seconds |
| **SSE Check Interval** | Every 2 seconds |
| **Idle Check Interval** | Every 10 seconds |
| **Max Alerts Shown** | 20 (configurable) |
| **Auto-mark Read** | On modal open |

## ✅ Verification Checklist

Test your notification system:

- [ ] Open your app in browser
- [ ] Open browser console (F12)
- [ ] You should see: `✅ Real-time connection established`
- [ ] Run `send_test_notification.php`
- [ ] Within 2 seconds, red dot appears on bell icon
- [ ] Click bell - notification appears
- [ ] Red dot disappears (marked as read)
- [ ] Console shows: `📡 Update received: ["alerts"]`

## 🎉 Summary

Your notification system is **already working** with:

✅ **Real-time delivery** - 2-second latency  
✅ **Smart SSE** - Same stream as orders  
✅ **Auto-read tracking** - Marks read on view  
✅ **Unread counter** - Red dot indicator  
✅ **Zero polling** - Efficient SSE push  
✅ **Works on cPanel** - No special setup needed  

**No additional setup required!** Just use `send_test_notification.php` to test it.
