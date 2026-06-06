/**
 * Paxyo Mini App Backend — Node.js Entry Point
 */
import express from 'express';
import cors from 'cors';
import compression from 'compression';
import 'dotenv/config';

import pool from './config/database.js';
import depositRouter from './routes/deposit.js';
import completeDepositRouter from './routes/completeDeposit.js';
import verifyDepositRouter from './routes/verifyDeposit.js';
import chapaCallbackRouter from './routes/chapaCallback.js';
import getDepositsRouter from './routes/getDeposits.js';
import getBalanceRouter from './routes/getBalance.js';
import getServicesRouter from './routes/getServices.js';
import ordersRouter from './routes/orders.js';
import appRouter from './routes/app.js';
import chatRouter from './routes/chat.js';
import getCategoriesRouter from './routes/getCategories.js';
import adminUsersRouter from './routes/admin.js';
import recommendedServicesRouter from './routes/recommendedServices.js';
import referralRouter from './routes/referral.js';

const app = express();

// Ensure database columns exist on startup
(async () => {
    try {
        const conn = await pool.getConnection();
        try {
            // Ensure orders custom_fields exists
            try {
                await conn.execute('ALTER TABLE orders ADD COLUMN custom_fields JSON AFTER status');
                console.log('[Startup] Checked/Added custom_fields column to orders table');
            } catch (e) {}

            // Ensure service_custom table exists
            await conn.execute(`
                CREATE TABLE IF NOT EXISTS service_custom (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    service_id INT NOT NULL UNIQUE,
                    is_enabled TINYINT DEFAULT 1,
                    custom_rate DECIMAL(10, 2),
                    profit_margin DECIMAL(5, 2),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            `);

            // Ensure custom_description column exists in service_custom
            try {
                await conn.execute('ALTER TABLE service_custom ADD COLUMN custom_description TEXT');
                console.log('[Startup] Checked/Added custom_description column to service_custom table');
            } catch (e) {}
        } finally {
            conn.release();
        }
    } catch (e) {
        console.error('[Startup] DB check failed:', e.message);
    }
})();

// cPanel/Passenger priority: Always use process.env.PORT if provided.
// On cPanel, this is usually a path to a socket, not a number.
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors({ optionsSuccessStatus: 200 }));
app.use(compression({
    level: 6,
    threshold: 1024
}));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Healthcheck
app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', time: new Date().toISOString() });
});

// DEBUG ROUTES - Test if server is running
app.get('/api/debug', (req, res) => {
    res.json({ status: 'ok', message: 'Server is running!' });
});

app.get('/api/debug/db', async (req, res) => {
    try {
        const conn = await pool.getConnection();
        const [tables] = await conn.query('SHOW TABLES');
        const [authRows] = await conn.query('SELECT COUNT(*) as cnt FROM auth');
        conn.release();
        res.json({ 
            status: 'success', 
            dbConnected: true, 
            tables: tables.length,
            userCount: authRows[0].cnt
        });
    } catch(e) {
        res.json({ 
            status: 'error', 
            dbConnected: false, 
            error: e.message,
            code: e.code,
            errno: e.errno
        });
    }
});

// Test DB connection
app.get('/api/test-db', async (req, res) => {
    try {
        const [rows] = await pool.execute('SELECT COUNT(*) as cnt FROM auth');
        res.json({ success: true, userCount: rows[0].cnt });
    } catch(e) {
        res.status(500).json({ error: e.message });
    }
});

// Chapa Routes
app.use('/api/deposit', depositRouter);
app.use('/api/complete-deposit', completeDepositRouter);
app.use('/api/verify-deposit', verifyDepositRouter);
app.use('/api/chapa-callback', chapaCallbackRouter);

// Dynamically load Telegram Webhook to prevent server crashes if file isn't uploaded
import('./routes/telegramWebhook.js')
    .then(module => {
        app.use('/api/telegram-webhook', module.default);
        console.log('[Startup] Telegram Webhook Router loaded successfully.');
    })
    .catch(err => {
        console.warn('[Startup] Skipping Telegram Webhook (file missing or error):', err.message);
    });

// User Data Routes
app.use('/api/deposits', getDepositsRouter);
app.use('/api/balance', getBalanceRouter);
app.use('/api/services', getServicesRouter);
app.use('/api/orders', ordersRouter);
app.use('/api/app', appRouter);
app.use('/api/chat', chatRouter);
app.use('/api/categories', getCategoriesRouter);
app.use('/api/admin', adminUsersRouter);
app.use('/api/services', recommendedServicesRouter);
app.use('/api/referral', referralRouter);

// Start server
// In cPanel/Passenger, we MUST NOT specify a port number if we want it to handle routing.
// However, the function requires one or it defaults to a random one.
// The trick is to listen on the variable provided by Passenger.
app.listen(PORT, () => {
    console.log(`🚀 Paxyo Backend running on port ${PORT}`);
});
