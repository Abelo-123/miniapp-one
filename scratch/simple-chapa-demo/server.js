import express from 'express';
import cors from 'cors';
import mysql from 'mysql2/promise';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3005;

// Enable CORS so the local frontend can communicate with the backend
app.use(cors());
app.use(express.json());

// Serve static files (like index.html) directly from this directory
app.use(express.static(__dirname));

// Database connection credentials (as requested)
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'paxyobot',
};

// Hardcoded TG User ID for testing
const TARGET_TG_ID = '123456789';

let pool;

async function initDB() {
    try {
        pool = mysql.createPool(dbConfig);
        console.log('🔌 Connecting to local MySQL...');
        
        // Ensure connection works
        const conn = await pool.getConnection();
        console.log('✅ Connected to MySQL successfully!');

        // 1. Create target tables if they do not exist
        await conn.execute(`
            CREATE TABLE IF NOT EXISTS auth (
                tg_id VARCHAR(255) PRIMARY KEY,
                balance DECIMAL(10, 2) DEFAULT 0.00,
                first_name VARCHAR(255) DEFAULT 'Test'
            )
        `);
        console.log('✅ Checked "auth" table.');

        await conn.execute(`
            CREATE TABLE IF NOT EXISTS deposits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                tx_ref VARCHAR(255) NOT NULL UNIQUE,
                status VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);
        console.log('✅ Checked "deposits" table.');

        // 2. Ensure our test user (tg_id: 123456789) exists
        await conn.execute(`
            INSERT IGNORE INTO auth (tg_id, balance, first_name)
            VALUES (?, 100.00, 'Test User')
        `, [TARGET_TG_ID]);
        console.log(`✅ Verified test user (tg_id: ${TARGET_TG_ID}) is present in database.`);

        conn.release();
    } catch (err) {
        console.error('❌ Database connection or setup failed:', err.message);
        console.log('👉 Make sure you have created the database "paxyobot" inside local phpMyAdmin / MySQL first.');
    }
}

// Get the user's current balance
app.get('/api/user-balance', async (req, res) => {
    try {
        const [rows] = await pool.execute('SELECT balance FROM auth WHERE tg_id = ?', [TARGET_TG_ID]);
        if (rows.length === 0) {
            return res.status(404).json({ success: false, error: 'User not found' });
        }
        res.json({ success: true, balance: parseFloat(rows[0].balance) });
    } catch (err) {
        console.error('Error fetching balance:', err.message);
        res.status(500).json({ success: false, error: err.message });
    }
});

// Initialize a payment by calling Chapa API securely and getting a checkout URL
app.post('/api/initialize-payment', async (req, res) => {
    const { amount } = req.body;

    if (!amount || isNaN(amount) || amount <= 0) {
        return res.status(400).json({ success: false, error: 'Invalid deposit amount' });
    }

    try {
        const tx_ref = `DEMO-${TARGET_TG_ID}-${Date.now()}-${Math.floor(Math.random() * 1000)}`;

        console.log(`[Initialization] Requesting Chapa checkout_url for ${amount} ETB...`);

        // Call Chapa API securely from the backend using the Secret Key
        const chapaResponse = await fetch('https://api.chapa.co/v1/transaction/initialize', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer CHASECK-WGUq6JVPIxSmjVSWTebh5UOOcshNscEd',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                amount: amount,
                currency: 'ETB',
                email: 'customer@paxyo.com',
                first_name: 'Test',
                last_name: 'User',
                tx_ref: tx_ref,
                callback_url: 'https://webhook.site/dummy-paxyo-callback',
                return_url: `http://localhost:${PORT}/close-popup.html`,
                customization: {
                    title: 'Demo Deposit',
                    description: 'Top-up demo user balance',
                }
            })
        });

        const chapaData = await chapaResponse.json();

        if (chapaResponse.status !== 200 || chapaData.status !== 'success') {
            console.warn('❌ [Initialization] Chapa API Error Details:', JSON.stringify(chapaData, null, 2));
            return res.status(400).json({ 
                success: false, 
                error: chapaData.message || 'Failed to initialize payment with Chapa' 
            });
        }

        const checkout_url = chapaData.data?.checkout_url;

        // Save the pending deposit inside the DB
        await pool.execute(
            'INSERT INTO deposits (user_id, amount, tx_ref, status) VALUES (?, ?, ?, ?)',
            [TARGET_TG_ID, amount, tx_ref, 'pending']
        );

        console.log(`[Database] Created pending deposit of ${amount} ETB for ${TARGET_TG_ID}. Ref: ${tx_ref}`);
        res.json({ success: true, tx_ref, checkout_url });
    } catch (err) {
        console.error('Error initializing payment:', err.message);
        res.status(500).json({ success: false, error: err.message });
    }
});

// Verify a payment with Chapa's secure API and update user balance
app.post('/api/verify-payment', async (req, res) => {
    const { tx_ref } = req.body;

    if (!tx_ref) {
        return res.status(400).json({ success: false, error: 'Missing transaction reference (tx_ref)' });
    }

    try {
        console.log(`[Verification] Verifying transaction ${tx_ref} with Chapa API...`);

        // Call Chapa API securely from the backend using the Secret Key
        const chapaResponse = await fetch(`https://api.chapa.co/v1/transaction/verify/${tx_ref}`, {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer CHASECK-WGUq6JVPIxSmjVSWTebh5UOOcshNscEd',
            }
        });

        const chapaData = await chapaResponse.json();

        if (chapaResponse.status !== 200 || chapaData.status !== 'success') {
            console.warn(`[Verification] Chapa returned error for ${tx_ref}:`, chapaData);
            return res.status(400).json({ 
                success: false, 
                error: chapaData.message || 'Verification failed on Chapa' 
            });
        }

        const chapaTxStatus = (chapaData.data?.status ?? '').toLowerCase();
        const isSuccessful = chapaTxStatus === 'success' || chapaTxStatus === 'paid';

        if (!isSuccessful) {
            return res.json({ success: false, message: `Payment is still ${chapaTxStatus || 'pending'}` });
        }

        // Run database queries in a transaction
        const conn = await pool.getConnection();
        try {
            await conn.beginTransaction();

            // Lock and fetch the pending deposit row to prevent duplicate updates
            const [deposits] = await conn.execute(
                'SELECT * FROM deposits WHERE tx_ref = ? FOR UPDATE',
                [tx_ref]
            );

            const deposit = deposits[0];

            if (!deposit) {
                await conn.rollback();
                conn.release();
                return res.status(404).json({ success: false, error: 'Deposit record not found' });
            }

            if (deposit.status === 'success') {
                await conn.rollback();
                conn.release();
                return res.json({ success: true, message: 'Already processed successfully' });
            }

            const paidAmount = parseFloat(chapaData.data.amount) || parseFloat(deposit.amount);

            // 1. Update deposit status to success
            await conn.execute(
                "UPDATE deposits SET status = 'success' WHERE id = ?",
                [deposit.id]
            );

            // 2. Add amount to user's balance inside auth table
            await conn.execute(
                'UPDATE auth SET balance = balance + ? WHERE tg_id = ?',
                [paidAmount, TARGET_TG_ID]
            );

            await conn.commit();
            conn.release();

            console.log(`[Database] SUCCESS! Wallet for tg_id ${TARGET_TG_ID} credited with +${paidAmount} ETB.`);
            res.json({ success: true, message: 'Payment verified and wallet credited!' });
        } catch (dbErr) {
            await conn.rollback();
            conn.release();
            throw dbErr;
        }

    } catch (err) {
        console.error('Error verifying payment:', err.message);
        res.status(500).json({ success: false, error: err.message });
    }
});

// Start Express Server
app.listen(PORT, async () => {
    await initDB();
    console.log(`🚀 Simple Payment Server running at http://localhost:${PORT}`);
});
