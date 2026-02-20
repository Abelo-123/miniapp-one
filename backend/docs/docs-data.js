const DOCS = {
    overview: {
        title: "Project Overview",
        desc: "Paxyo is a Social Media Marketing (SMM) panel running as a Telegram Mini App and a standalone web app with Google OAuth. Users browse/order social media services, deposit funds via Chapa (ETB), track orders in real-time via SSE, and chat with admin.",
        stack: [
            { layer: "Backend", tech: "PHP 7.4+ (procedural, no framework)" },
            { layer: "Database", tech: "MySQL via mysqli" },
            { layer: "Frontend", tech: "Single SPA (smm.php), Vanilla JS ES6+, CSS" },
            { layer: "SMM Provider", tech: "GodOfPanel API v2" },
            { layer: "Payment", tech: "Chapa Inline Checkout SDK (ETB)" },
            { layer: "Telegram SDK", tech: "telegram-web-app.js v7.10+" },
            { layer: "Auth", tech: "Telegram HMAC-SHA256 + Google OAuth 2.0" },
            { layer: "Real-time", tech: "Server-Sent Events (SSE)" },
            { layer: "Admin Alerts", tech: "HTTP POST to Node.js bot backend" }
        ]
    },
    endpoints: [
        {
            id: "telegram-auth",
            method: "POST",
            path: "/telegram_auth.php",
            file: "telegram_auth.php",
            title: "Telegram Auth",
            desc: "Validates Telegram initData via HMAC-SHA256, creates/updates user, establishes session.",
            request: '{ "initData": "<telegram_initData_string>" }',
            response: '{\n  "success": true,\n  "user": {\n    "id": 12345,\n    "first_name": "John",\n    "display_name": "John Doe",\n    "photo_url": "https://...",\n    "balance": 100.50\n  }\n}',
            logic: [
                "Parse initData URL-encoded key-value pairs",
                "Extract & remove hash parameter",
                "Sort remaining params alphabetically",
                "Create data_check_string by joining with \\n",
                "Compute secret_key = HMAC-SHA256('WebAppData', BOT_TOKEN)",
                "Compute hash = HMAC-SHA256(data_check_string, secret_key)",
                "Compare computed hash with received hash",
                "Check auth_date within 86400s expiry",
                "Create user if new (insert auth row + welcome alert + notify admin)",
                "Update profile if existing user",
                "Set session: tg_id, tg_first_name, tg_photo_url"
            ]
        },
        {
            id: "google-auth",
            method: "GET",
            path: "/google_auth.php",
            file: "google_auth.php",
            title: "Google OAuth",
            desc: "Standard OAuth 2.0 Authorization Code flow for web users.",
            request: "?action=login → redirects to Google\n?code=...&state=... → callback from Google",
            response: "Redirect to index.php on success",
            logic: [
                "?action=login → redirect to Google consent screen with CSRF state token",
                "Google callback → verify state token",
                "Exchange code for access_token via Google Token endpoint",
                "Fetch user info (id, email, name, picture) from Google UserInfo",
                "Generate negative internal ID: -(abs(crc32(googleId)) % 1e9 + 1e9)",
                "Create/update user in auth table",
                "Set session + cookie, redirect to index.php"
            ]
        },
        {
            id: "get-services",
            method: "GET",
            path: "/get_service.php",
            file: "get_service.php",
            title: "Get Services",
            desc: "Fetches SMM services from GodOfPanel API with stale-while-revalidate caching and ETag support.",
            request: "?refresh=1 (optional, forces cache refresh)",
            response: '[{\n  "service": 123,\n  "name": "Instagram Followers",\n  "type": "Default",\n  "rate": "5.50",\n  "min": 100, "max": 10000,\n  "category": "Instagram Followers",\n  "refill": true,\n  "average_time": "2-4 Hours"\n}]',
            logic: [
                "If cache/services.json exists and < 5min old → serve from cache",
                "If stale but exists → serve stale + background refresh",
                "If ?refresh=1 → fetch fresh from API, save to cache",
                "ETag support for HTTP-level caching (304 Not Modified)",
                "Merge with service_adjustments table for average_time",
                "Safety check: if API returns < 10 services, use cache instead",
                "Rate multiplier applied client-side, not here"
            ]
        },
        {
            id: "get-recommended",
            method: "GET",
            path: "/get_recommended.php",
            file: "get_recommended.php",
            title: "Get Recommended",
            desc: "Returns admin-curated recommended service IDs.",
            request: "No params",
            response: "[123, 456, 789]",
            logic: ["Query admin_recommended_services table", "Return array of service_id integers"]
        },
        {
            id: "process-order",
            method: "POST",
            path: "/process_order.php",
            file: "process_order.php",
            title: "Place Order",
            desc: "Validates input, checks balance & maintenance mode, calls external API, deducts balance, saves order.",
            request: '{\n  "service": 123,\n  "link": "https://instagram.com/p/...",\n  "quantity": 1000,\n  "comments": "..." (optional),\n  "answer_number": 3 (optional, Poll type)\n}',
            response: '{\n  "success": true,\n  "order_id": 12345,\n  "charge": 55.00,\n  "new_balance": 45.00\n}',
            logic: [
                "Auth check → get user_id from session",
                "Validate: service exists, link not empty, qty between min/max, qty multiple of 10",
                "Fetch service data from cache/services.json",
                "Get rate_multiplier from settings table",
                "Get active holiday discount from holidays table",
                "Calculate charge: (qty/1000) × rate × multiplier × (1 - discount/100)",
                "Check user balance ≥ charge",
                "Check maintenance mode (only allowed IDs can proceed)",
                "POST to GodOfPanel API: action=add",
                "On success → deduct balance, insert order, touch cron flag file",
                "Notify admin via notify_bot_admin()"
            ]
        },
        {
            id: "get-orders",
            method: "GET",
            path: "/get_orders.php",
            file: "get_orders.php",
            title: "Get Orders",
            desc: "Returns user's order history (last 50 orders). Optimized with gzip and early session release.",
            request: "No params",
            response: '{ "orders": [{ "id": 1, "api_order_id": 12345, "service_name": "...", "status": "completed", ... }] }',
            logic: ["Auth check", "Early session_write_close()", "Prepared statement with LIMIT 50", "gzip compression enabled"]
        },
        {
            id: "check-order-status",
            method: "GET",
            path: "/check_order_status.php",
            file: "check_order_status.php",
            title: "Sync Order Status",
            desc: "Triggers syncOrderStatuses() to batch-check active orders against external API.",
            request: "No params",
            response: '{ "success": true, "checked": 5, "updated": 2, "updates": [...] }',
            logic: [
                "Calls syncOrderStatuses(user_id, conn) from order_manager.php",
                "Select orders with status IN (pending, processing, in_progress)",
                "POST to API: action=status, orders=comma-separated IDs",
                "For cancelled/refunded → full refund (add charge back to balance)",
                "For partial → partial refund: charge × (remains / quantity)",
                "Update orders table with new status, start_count, remains"
            ]
        },
        {
            id: "user-actions",
            method: "POST",
            path: "/user_actions.php",
            file: "user_actions.php",
            title: "Refill Order",
            desc: "Handles refill requests for completed orders with refill-capable services.",
            request: '{ "action": "refill", "order_id": 123 }',
            response: '{ "success": true, "message": "Refill request sent! ID: 456" }',
            logic: [
                "Auth check + verify order belongs to user",
                "Look up service in cache, check refill field",
                "POST to API: action=refill, order=api_order_id",
                "Notify admin on success"
            ]
        },
        {
            id: "deposit-handler",
            method: "POST",
            path: "/deposit_handler.php",
            file: "deposit_handler.php",
            title: "Process Deposit",
            desc: "Records deposit, updates user balance atomically. Prevents double-counting via reference_id check.",
            request: '{ "amount": 100.00, "reference_id": "chapa-ref-123" }',
            response: '{ "status": "success", "new_balance": 234.56 }',
            logic: [
                "Auth check, validate amount > 0",
                "Check reference_id doesn't already exist (prevent double counting)",
                "Insert into deposits table with status='completed'",
                "Atomic balance update: UPDATE auth SET balance = balance + amount",
                "Notify admin via bot"
            ]
        },
        {
            id: "get-deposits",
            method: "GET",
            path: "/get_deposits.php",
            file: "get_deposits.php",
            title: "Get Deposits",
            desc: "Returns user's last 5 deposit records.",
            request: "No params",
            response: '[{ "id": 1, "amount": "100.00", "reference_id": "ref-123", "status": "completed", "created_at": "..." }]',
            logic: ["Auth check", "SELECT last 5 from deposits WHERE user_id"]
        },
        {
            id: "get-alerts",
            method: "GET",
            path: "/get_alerts.php",
            file: "get_alerts.php",
            title: "Get Alerts",
            desc: "Returns user's notifications with unread count.",
            request: "No params",
            response: '{ "alerts": [...], "unread_count": 3 }',
            logic: ["Auth check", "SELECT last 20 from user_alerts", "Count unread (is_read=0)"]
        },
        {
            id: "mark-alerts-read",
            method: "GET",
            path: "/mark_alerts_read.php",
            file: "mark_alerts_read.php",
            title: "Mark Alerts Read",
            desc: "Marks all unread alerts as read for the current user.",
            request: "No params",
            response: '{ "success": true }',
            logic: ["Auth check", "UPDATE user_alerts SET is_read=1 WHERE user_id AND is_read=0"]
        },
        {
            id: "chat-api",
            method: "POST",
            path: "/chat_api.php",
            file: "chat_api.php",
            title: "Chat API",
            desc: "Send/fetch chat messages. Messages stored in per-user JSON files + DB.",
            request: 'Send: { "action": "send", "message": "Hello" }\nFetch: { "action": "fetch" } or GET ?action=get',
            response: '{ "success": true, "messages": [{ "sender": "user", "message": "...", "created_at": "..." }] }',
            logic: [
                "POST send: rate limit, save to chat_data/chat_{uid}.json",
                "Also INSERT into chat_messages DB table",
                "Notify admin via bot",
                "Max 1000 chars per message, keep 100 messages per user",
                "GET/fetch: read last 50 from JSON file"
            ]
        },
        {
            id: "chat-stream",
            method: "SSE",
            path: "/chat_stream.php",
            file: "chat_stream.php",
            title: "Chat SSE Stream",
            desc: "Real-time chat updates via Server-Sent Events. Watches file modification time.",
            request: "EventSource connection",
            response: 'data: { "type": "messages", "messages": [...] }',
            logic: [
                "Auth check, release session lock",
                "Send initial messages from user's chat JSON file",
                "Loop max 120s, sleep 2s per iteration",
                "Check filemtime() of chat JSON → if changed, send new messages",
                "Send heartbeat otherwise",
                "Client auto-reconnects on error"
            ]
        },
        {
            id: "realtime-stream",
            method: "SSE",
            path: "/realtime_stream.php",
            file: "realtime_stream.php",
            title: "Realtime SSE Stream",
            desc: "THE MAIN REAL-TIME SYSTEM. Unified stream for orders, alerts, balance, and maintenance updates via MD5 state hashing.",
            request: "EventSource connection",
            response: 'data: {\n  "type": "update",\n  "changes": ["orders", "balance"],\n  "data": { "orders": [...], "alerts": [...], "balance": 100.50, "maintenance": {...} }\n}',
            logic: [
                "Auth check, release session lock",
                "Load last state from temp/paxyo_state_{uid}.json",
                "Loop max 30s, sleep 2s per iteration",
                "Compute: orders_hash (MD5 of IDs+statuses+remains), alerts_hash, balance, maintenance_mode",
                "Compare with last state → if changed, send full update with changed categories",
                "If no change → send heartbeat",
                "Check cron flag for idle mode (slower heartbeat when no active orders)",
                "Save final state to file"
            ]
        },
        {
            id: "heartbeat",
            method: "GET",
            path: "/heartbeat.php",
            file: "heartbeat.php",
            title: "Heartbeat",
            desc: "Ultra-lightweight online status ping. Updates last_seen timestamp. Called every 30s.",
            request: "No params",
            response: '{"ok":1}',
            logic: ["Auth check", "Early session_write_close()", "UPDATE auth SET last_seen=NOW()", "8-byte minimal response"]
        },
        {
            id: "webhook-handler",
            method: "POST",
            path: "/webhook_handler.php",
            file: "webhook_handler.php",
            title: "External Webhook",
            desc: "Receives push notifications from SMM provider about order status changes.",
            request: '{ "order_id": 12345, "status": "completed", "start_count": 500, "remains": 0 }',
            response: '{ "success": true, "order_id": 1, "status": "completed", "refund_amount": 0 }',
            logic: [
                "Parse incoming JSON (order_id, status, start_count, remains)",
                "Find local order by api_order_id",
                "Update orders table",
                "Handle refunds for cancelled/partial orders",
                "Attempt WebSocket notification (tcp://127.0.0.1:8080)"
            ]
        },
        {
            id: "cron-check",
            method: "CLI",
            path: "/cron_check_orders.php",
            file: "cron_check_orders.php",
            title: "Cron Job",
            desc: "Smart cron that only runs when active orders exist. Syncs order statuses for all users.",
            request: "Run via system cron every 10s (6 cron jobs with sleep offsets)",
            response: "CLI output: [timestamp] Checking N active orders...",
            logic: [
                "Check if ANY active orders exist in DB",
                "If none → delete paxyo_cron_active.flag, exit silently",
                "If active → create/update flag file",
                "Get distinct user_ids with active orders",
                "For each user → call syncOrderStatuses()",
                "Log updates per user"
            ]
        },
        {
            id: "index-router",
            method: "GET",
            path: "/index.php",
            file: "index.php",
            title: "Entry Router",
            desc: "Smart router detecting Telegram vs web users. Sets critical headers for iframe embedding.",
            request: "Browser/Telegram request",
            response: "Includes smm.php or redirects to login.php",
            logic: [
                "Set headers: CORS, frame-ancestors *, X-Frame-Options ALLOWALL",
                "Detect Telegram via User-Agent, Referer, tgWebApp* params",
                "Handle ?id= deep link parameter",
                "Telegram → include smm.php",
                "Authenticated web user → include smm.php",
                "Unauthenticated → include smm.php (guest mode)"
            ]
        }
    ],
    database: [
        {
            table: "auth", desc: "Users", cols: [
                ["tg_id", "BIGINT (PK)", "Telegram user ID. Google users: negative CRC32 hash"],
                ["google_id", "VARCHAR", "Google account ID (nullable)"],
                ["email", "VARCHAR", "User email from Google (nullable)"],
                ["first_name", "VARCHAR", "Display name"],
                ["last_name", "VARCHAR", "Last name (nullable)"],
                ["photo_url", "TEXT", "Profile photo URL"],
                ["balance", "DECIMAL(10,2)", "Current balance in ETB"],
                ["is_blocked", "TINYINT", "1=blocked, 0=active"],
                ["auth_provider", "VARCHAR", "'telegram' or 'google'"],
                ["last_login", "DATETIME", "Last authentication time"],
                ["last_seen", "DATETIME", "Last heartbeat timestamp"],
                ["created_at", "DATETIME", "Account creation time"]
            ]
        },
        {
            table: "orders", desc: "Order History", cols: [
                ["id", "INT (PK, AI)", "Local order ID"],
                ["user_id", "BIGINT (FK)", "→ auth.tg_id"],
                ["api_order_id", "INT", "Order ID from external provider"],
                ["service_id", "INT", "Service ID from provider"],
                ["service_name", "VARCHAR", "Name of service"],
                ["link", "TEXT", "URL provided by user"],
                ["quantity", "INT", "Items ordered"],
                ["charge", "DECIMAL(10,2)", "Amount deducted"],
                ["status", "VARCHAR", "pending|processing|in_progress|completed|cancelled|partial"],
                ["remains", "INT", "Remaining items to deliver"],
                ["start_count", "INT", "Initial count before order"],
                ["created_at", "DATETIME", "Order placement time"],
                ["updated_at", "DATETIME", "Last status update"]
            ]
        },
        {
            table: "deposits", desc: "Deposit Records", cols: [
                ["id", "INT (PK, AI)", ""],
                ["user_id", "BIGINT (FK)", "→ auth.tg_id"],
                ["amount", "DECIMAL(10,2)", "Deposit amount in ETB"],
                ["reference_id", "VARCHAR", "Chapa transaction reference"],
                ["status", "VARCHAR", "completed|pending|failed"],
                ["created_at", "DATETIME", ""]
            ]
        },
        {
            table: "settings", desc: "App Settings (KV)", cols: [
                ["setting_key", "VARCHAR (PK)", "Key name"],
                ["setting_value", "TEXT", "Value: rate_multiplier, maintenance_mode, maintenance_allowed_ids, marquee_text"]
            ]
        },
        {
            table: "holidays", desc: "Discount Holidays", cols: [
                ["id", "INT (PK)", ""],
                ["name", "VARCHAR", "Holiday name"],
                ["discount_percent", "INT", "Percentage discount"],
                ["status", "VARCHAR", "active|inactive"],
                ["start_date", "DATE", ""],
                ["end_date", "DATE", ""]
            ]
        },
        {
            table: "user_alerts", desc: "User Notifications", cols: [
                ["id", "INT (PK, AI)", ""],
                ["user_id", "BIGINT (FK)", "→ auth.tg_id"],
                ["message", "TEXT", "Alert message"],
                ["is_read", "TINYINT", "0=unread, 1=read"],
                ["created_at", "DATETIME", ""]
            ]
        },
        {
            table: "chat_messages", desc: "Chat Messages", cols: [
                ["id", "INT (PK, AI)", ""],
                ["user_id", "BIGINT (FK)", "→ auth.tg_id"],
                ["sender", "ENUM", "'user' or 'admin'"],
                ["message", "TEXT", "Message content"],
                ["created_at", "DATETIME", ""]
            ]
        },
        {
            table: "service_adjustments", desc: "Custom Service Metadata", cols: [
                ["service_id", "INT (PK)", "Service ID from provider"],
                ["average_time", "VARCHAR", "Human-readable delivery time e.g. '2-4 Hours'"]
            ]
        },
        {
            table: "admin_recommended_services", desc: "Curated Services", cols: [
                ["service_id", "INT (PK)", "Service ID from provider"]
            ]
        }
    ],
    dbHelpers: {
        title: "Database Helper Functions (db.php)",
        funcs: [
            { name: "db_select(table, where_col, where_val, fields)", desc: "Get single row/field", example: "db_select('auth', 'tg_id', 111, 'balance') → 100.50" },
            { name: "db_insert(table, data)", desc: "Insert row, returns ID", example: "db_insert('auth', ['tg_id'=>111, 'balance'=>50])" },
            { name: "db_update(table, where_col, where_val, data)", desc: "Update row", example: "db_update('auth', 'tg_id', 111, ['balance'=>200])" },
            { name: "db_delete(table, where_col, where_val)", desc: "Delete row", example: "db_delete('auth', 'tg_id', 111)" },
            { name: "db(action, table, ...args)", desc: "All-in-one shortcut", example: "db('get', 'auth', 'tg_id', 111, 'balance')" },
            { name: "db_query(sql)", desc: "Raw SQL, returns array of rows", example: "db_query('SELECT * FROM auth WHERE balance > 100')" },
            { name: "db_escape(str)", desc: "Escape string for SQL", example: "db_escape(userInput)" }
        ]
    },
    notifications: [
        { type: "newuser", fields: "uid, uuid (name)", when: "New user registered" },
        { type: "neworder", fields: "uid, order, service, amount, panel, pb", when: "Order placed" },
        { type: "deposit", fields: "uid, uuid (name), amount", when: "Deposit completed" },
        { type: "refund", fields: "uid, order, amount", when: "Full refund applied" },
        { type: "partial", fields: "uid, order, amount, uuid (remains)", when: "Partial refund" },
        { type: "chat", fields: "uid, message", when: "New chat message" },
        { type: "order_error", fields: "uid, service, error", when: "Order API error" },
        { type: "refill", fields: "uid, order, uuid (refill ID), service", when: "Refill requested" }
    ],
    config: [
        { key: "MySQL credentials", file: "db.php", purpose: "Database connection" },
        { key: "TELEGRAM_BOT_TOKEN", file: "config_telegram.php", purpose: "initData HMAC validation" },
        { key: "BOT_API_URL", file: "config_telegram.php", purpose: "Admin notification endpoint" },
        { key: "TELEGRAM_INIT_DATA_EXPIRY", file: "config_telegram.php", purpose: "Auth expiry (86400s)" },
        { key: "GOOGLE_CLIENT_ID", file: "config_google.php", purpose: "Google OAuth" },
        { key: "GOOGLE_CLIENT_SECRET", file: "config_google.php", purpose: "Google OAuth" },
        { key: "GOOGLE_REDIRECT_URI", file: "config_google.php", purpose: "OAuth callback URL" },
        { key: "SMM API Key", file: "get_service.php, process_order.php, order_manager.php", purpose: "GodOfPanel API auth" },
        { key: "Chapa Public Key", file: "smm.php (JS)", purpose: "Chapa inline checkout" }
    ],
    security: [
        "Telegram initData: HMAC-SHA256 signature verification using bot token",
        "Google OAuth: CSRF protection via random state parameter",
        "Session Cookie: SameSite=None; Secure for cross-origin Telegram iframe",
        "SQL Injection: db_escape() + prepared statements throughout",
        "XSS Prevention: escapeHtml() in frontend JS for user-generated content",
        "Content Security Policy: frame-ancestors * for Telegram embedding",
        "Rate Limiting: Chat messages have server-side rate limiting",
        "Balance Race Condition: Atomic SQL UPDATE balance = balance - X"
    ],
    patterns: [
        { name: "Stale-While-Revalidate", desc: "Services served from file cache immediately; background refresh updates cache for next request." },
        { name: "Pre-rendering", desc: "Category and service lists are pre-rendered in DOM when platform/category selected, so modals open instantly." },
        { name: "Early Session Lock Release", desc: "session_write_close() called immediately after reading session data to prevent blocking." },
        { name: "Gzip Compression", desc: "All JSON API responses use ob_gzhandler." },
        { name: "SSE State Hashing", desc: "MD5 hashes of concatenated data detect changes efficiently, only sending full data when changed." },
        { name: "Cron Flag File", desc: "paxyo_cron_active.flag prevents cron from running when no active orders." },
        { name: "Optimistic UI", desc: "Chat messages appear instantly before server confirmation." },
        { name: "Quantity × 10 Rule", desc: "All quantities must be multiples of 10 (validated client and server side)." }
    ],
    backendFiles: [
        { file: "db.php", title: "Database Connection & Helpers", desc: "MySQL connection setup + db_select, db_insert, db_update, db_delete, db(), db_query helper functions." },
        { file: "config_telegram.php", title: "Telegram Config", desc: "Defines TELEGRAM_BOT_TOKEN, TELEGRAM_INIT_DATA_EXPIRY, BOT_API_URL constants." },
        { file: "config_google.php", title: "Google OAuth Config", desc: "Defines GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI, endpoint URLs." },
        { file: "utils_bot.php", title: "Admin Notification Helper", desc: "notify_bot_admin() — sends JSON POST to the Node.js bot backend for admin alerts." },
        { file: "order_manager.php", title: "Order Status Sync Engine", desc: "syncOrderStatuses() — batch-checks active orders against external API, handles refunds for cancelled/partial orders." },
        { file: "error_logger.php", title: "Error Logger", desc: "Custom error logging utility for tracking issues in production." },
        { file: "login.php", title: "Web Login Page", desc: "Google Sign-In button page for non-Telegram web users." },
        { file: "logout.php", title: "Logout Handler", desc: "Destroys session and cookies, redirects to login page." },
        { file: "bot.js", title: "Telegram Bot (Node.js)", desc: "Node.js bot backend — receives admin notifications, sends Telegram messages, handles bot commands." },
        { file: "telegram_auth.php", title: "Telegram Auth", desc: "Validates initData via HMAC-SHA256, creates/updates user, establishes session." },
        { file: "google_auth.php", title: "Google OAuth Handler", desc: "Full OAuth 2.0 Authorization Code flow — redirects, token exchange, user creation." },
        { file: "get_service.php", title: "Get Services", desc: "Fetches SMM services with stale-while-revalidate caching and ETag support." },
        { file: "get_recommended.php", title: "Get Recommended", desc: "Returns admin-curated recommended service IDs." },
        { file: "process_order.php", title: "Place Order", desc: "Validates, checks balance, calls external API, deducts balance, saves order." },
        { file: "get_orders.php", title: "Get Orders", desc: "Returns user's order history (last 50)." },
        { file: "check_order_status.php", title: "Sync Order Status", desc: "Triggers syncOrderStatuses() for current user." },
        { file: "user_actions.php", title: "User Actions (Refill)", desc: "Handles refill requests for completed orders." },
        { file: "deposit_handler.php", title: "Process Deposit", desc: "Records deposit, updates balance atomically." },
        { file: "get_deposits.php", title: "Get Deposits", desc: "Returns user's last 5 deposit records." },
        { file: "get_alerts.php", title: "Get Alerts", desc: "Returns user's notifications with unread count." },
        { file: "mark_alerts_read.php", title: "Mark Alerts Read", desc: "Marks all unread alerts as read." },
        { file: "chat_api.php", title: "Chat API", desc: "Send/fetch chat messages via JSON files + DB." },
        { file: "chat_stream.php", title: "Chat SSE Stream", desc: "Real-time chat updates via Server-Sent Events." },
        { file: "realtime_stream.php", title: "Realtime SSE Stream", desc: "Unified real-time stream for orders, alerts, balance, maintenance." },
        { file: "heartbeat.php", title: "Heartbeat", desc: "Ultra-lightweight online status ping." },
        { file: "webhook_handler.php", title: "External Webhook", desc: "Receives order status webhooks from SMM provider." },
        { file: "cron_check_orders.php", title: "Cron Job", desc: "Smart cron that syncs order statuses for all users." },
        { file: "index.php", title: "Entry Router", desc: "Smart router detecting Telegram vs web users." },
        { file: "websocket_server.php", title: "WebSocket Server", desc: "WebSocket server for real-time push notifications." },
        { file: "tg_webhook_handler.php", title: "Telegram Webhook", desc: "Handles incoming Telegram bot webhook events." },
        { file: "api_save_phone.php", title: "Save Phone API", desc: "Saves user phone number for payment processing." },
        { file: "api_check_phone.php", title: "Check Phone API", desc: "Checks if a phone number is already registered." },
        { file: "updates.php", title: "Updates Handler", desc: "Handles app update notifications and version checks." }
    ]
};
