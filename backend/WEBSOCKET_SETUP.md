# WebSocket Real-time Order System - Setup Guide

## Architecture

This system uses **true WebSocket** connections for real-time updates with **ZERO polling**:

1. **WebSocket Server** (`websocket_server.php`) - Maintains persistent connections with clients
2. **Webhook Handler** (`webhook_handler.php`) - Receives updates from GodOfPanel API
3. **Frontend Client** (in `smm.php`) - Connects to WebSocket for instant updates

## How It Works

### Flow:
1. User opens the app → WebSocket connection established
2. GodOfPanel API sends webhook when order status changes → `webhook_handler.php`
3. Webhook handler processes refund (if needed) and notifies WebSocket server
4. WebSocket server pushes update to connected clients **instantly**
5. Frontend receives update and shows notification + updates UI

### No Polling!
- No repeated API calls
- No server-side loops checking status
- Updates happen **only when something actually changes**

## Setup Instructions

### Step 1: Start WebSocket Server

Open a terminal and run:
```bash
cd d:\next\xampp\htdocs\paxyo
d:\next\xampp\php\php.exe websocket_server.php
```

Keep this running in the background. You should see:
```
WebSocket server started on 0.0.0.0:8080
```

### Step 2: Configure GodOfPanel Webhook

1. Log into your GodOfPanel dashboard
2. Go to API Settings → Webhooks
3. Add webhook URL: `https://yoursite.com/webhook_handler.php`
4. Select events: Order Status Changed, Order Completed, Order Canceled

### Step 3: Update Frontend WebSocket URL

In `smm.php`, find line ~650 and update:
```javascript
const wsUrl = 'ws://your-actual-domain.com:8080';
```

For local testing: `ws://localhost:8080`
For production: `ws://yoursite.com:8080` or `wss://yoursite.com:8080` (SSL)

### Step 4: Firewall Configuration

Make sure port 8080 is open:
```bash
# Windows Firewall
netsh advfirewall firewall add rule name="WebSocket" dir=in action=allow protocol=TCP localport=8080
```

## Refund Logic

### Canceled Orders
- **Trigger**: When order status changes to `canceled`
- **Action**: Full refund of `charge` amount
- **Instant**: Refund happens immediately when webhook received

### Partial Orders  
- **Trigger**: When order status changes to `partial`
- **Action**: Refund = `charge × (remains / quantity)`
- **Example**: 1000 qty order, 300 remains → 30% refund

## Testing

### Test Webhook Manually:
```bash
curl -X POST http://localhost/paxyo/webhook_handler.php \
  -H "Content-Type: application/json" \
  -d '{"order_id":"12345","status":"canceled"}'
```

### Check Logs:
- WebSocket: Terminal where `websocket_server.php` is running
- Refunds: Check `d:\next\xampp\apache\logs\error.log`

## Production Deployment

### Use Process Manager (Windows)
Install NSSM to run WebSocket server as Windows service:
```bash
nssm install PaxyoWebSocket "d:\next\xampp\php\php.exe" "d:\next\xampp\htdocs\paxyo\websocket_server.php"
nssm start PaxyoWebSocket
```

### SSL/WSS Support
For production, use `wss://` (secure WebSocket):
1. Get SSL certificate
2. Update WebSocket server to use SSL context
3. Update frontend to use `wss://` instead of `ws://`

## Advantages Over Polling

✅ **Zero Server Load** - No repeated API calls
✅ **Instant Updates** - Sub-second latency  
✅ **Scalable** - Handles thousands of concurrent connections
✅ **Battery Friendly** - No constant polling drains mobile battery
✅ **Real-time Refunds** - Money back instantly when order canceled

## Troubleshooting

**WebSocket won't connect:**
- Check if server is running: `netstat -an | findstr 8080`
- Check firewall settings
- Verify URL in frontend matches server address

**Refunds not working:**
- Check `error.log` for refund messages
- Verify webhook is configured in GodOfPanel
- Test webhook manually with curl

**Orders not updating:**
- Check WebSocket console logs in browser
- Verify user_id is correct
- Check if webhook handler is receiving data
