import { Router } from 'express';
import pool from '../config/database.js';
import { getTelegramUserId } from '../lib/auth.js';

const router = Router();

// Helper to get authenticated user ID
function getAuthUserId(req) {
    const { initData, user_id } = req.body.initData ? req.body : req.query;
    let tgId = getTelegramUserId(initData);
    if (!tgId) {
        tgId = user_id || req.body.user_id || req.query.user_id || 'unauth_local_user';
    }
    return tgId;
}

// 1. Submit a Withdrawal Request
router.post('/request', async (req, res) => {
    try {
        const { amount: rawAmount, full_name, bank_name, account_number } = req.body;
        const amount = parseFloat(rawAmount) || 0;
        const userId = getAuthUserId(req);

        if (amount <= 0) {
            return res.json({ success: false, error: 'Amount must be greater than zero' });
        }
        if (!full_name || !bank_name || !account_number) {
            return res.json({ success: false, error: 'All bank details (Full Name, Bank Name, Account Number) are required' });
        }

        const conn = await pool.getConnection();
        try {
            await conn.beginTransaction();

            // Lock user's auth row to check referral balance safely
            const [users] = await conn.execute('SELECT referral_balance FROM auth WHERE tg_id = ? FOR UPDATE', [userId]);
            const user = users[0];

            if (!user) {
                await conn.rollback();
                conn.release();
                return res.json({ success: false, error: 'User not found' });
            }

            const referralBalance = parseFloat(user.referral_balance) || 0;

            if (amount > referralBalance) {
                await conn.rollback();
                conn.release();
                return res.json({ success: false, error: `Insufficient referral commission balance. You have ${referralBalance.toFixed(2)} ETB.` });
            }

            // Deduct referral balance
            await conn.execute('UPDATE auth SET referral_balance = referral_balance - ? WHERE tg_id = ?', [amount, userId]);

            // Insert into withdrawals table
            await conn.execute(
                `INSERT INTO withdrawals (user_id, amount, full_name, bank_name, account_number, status) 
                 VALUES (?, ?, ?, ?, ?, 'pending')`,
                [userId, amount, full_name, bank_name, account_number]
            );

            // Log to ledger
            const newReferralBalance = referralBalance - amount;
            await conn.execute(
                `INSERT INTO transactions (user_id, type, amount, balance_after, reference_type, description) 
                 VALUES (?, 'referral_withdrawal', ?, ?, 'withdrawal', ?)`,
                [userId, -amount, newReferralBalance, `Referral withdrawal request to ${bank_name}`]
            );

            await conn.commit();
            conn.release();

            return res.json({ success: true, new_referral_balance: newReferralBalance });
        } catch (err) {
            await conn.rollback();
            conn.release();
            throw err;
        }
    } catch (err) {
        console.error('[withdraw/request] Error:', err);
        return res.json({ success: false, error: 'Database error: ' + err.message });
    }
});

// 2. Fetch User Withdrawal History
router.get('/history', async (req, res) => {
    try {
        const userId = getAuthUserId(req);
        const [rows] = await pool.execute('SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC', [userId]);
        
        // Fetch current referral balance
        const [users] = await pool.execute('SELECT referral_balance FROM auth WHERE tg_id = ?', [userId]);
        const referralBalance = users[0] ? parseFloat(users[0].referral_balance) : 0;

        return res.json({ success: true, history: rows, referral_balance: referralBalance });
    } catch (err) {
        console.error('[withdraw/history] Error:', err);
        return res.json({ success: false, error: 'Database error: ' + err.message });
    }
});

// 3. Admin: Fetch All Withdrawal Requests
router.get('/admin/list', async (req, res) => {
    try {
        const [rows] = await pool.execute(`
            SELECT w.*, a.username, a.first_name, a.last_name 
            FROM withdrawals w 
            LEFT JOIN auth a ON w.user_id = a.tg_id 
            ORDER BY w.created_at DESC
        `);
        return res.json({ success: true, list: rows });
    } catch (err) {
        console.error('[withdraw/admin/list] Error:', err);
        return res.json({ success: false, error: 'Database error: ' + err.message });
    }
});

// 4. Admin: Approve / Mark Done a request
router.post('/admin/approve', async (req, res) => {
    try {
        const { id } = req.body;
        if (!id) {
            return res.json({ success: false, error: 'Missing withdrawal ID' });
        }

        const conn = await pool.getConnection();
        try {
            await conn.beginTransaction();

            const [withdrawals] = await conn.execute('SELECT * FROM withdrawals WHERE id = ? FOR UPDATE', [id]);
            const w = withdrawals[0];

            if (!w) {
                await conn.rollback();
                conn.release();
                return res.json({ success: false, error: 'Withdrawal request not found' });
            }

            if (w.status === 'done') {
                await conn.rollback();
                conn.release();
                return res.json({ success: false, error: 'Withdrawal is already completed' });
            }

            // Update status to done
            await conn.execute('UPDATE withdrawals SET status = \'done\' WHERE id = ?', [id]);

            // Notify user
            await conn.execute(
                'INSERT INTO alerts (user_id, title, message, type) VALUES (?, ?, ?, \'success\')',
                [w.user_id, 'Withdrawal Done', `Your withdrawal request of ${parseFloat(w.amount).toFixed(2)} ETB has been marked as DONE and transferred to your bank account!`, 'success']
            );

            await conn.commit();
            conn.release();

            return res.json({ success: true });
        } catch (err) {
            await conn.rollback();
            conn.release();
            throw err;
        }
    } catch (err) {
        console.error('[withdraw/admin/approve] Error:', err);
        return res.json({ success: false, error: 'Database error: ' + err.message });
    }
});

export default router;
