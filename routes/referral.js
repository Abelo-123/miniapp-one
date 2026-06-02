import { Router } from 'express';
import pool from '../config/database.js';
import { getTelegramUserId } from '../lib/auth.js';

const router = Router();

router.post('/apply', async (req, res) => {
    const { initData, referralCode } = req.body;
    const tgId = getTelegramUserId(initData);
    
    if (!tgId) {
        return res.json({ success: false, error: 'Unauthorized' });
    }

    if (!referralCode || typeof referralCode !== 'string') {
        return res.json({ success: false, error: 'Invalid referral code' });
    }

    try {
        // 1. Get current user
        const [users] = await pool.execute('SELECT * FROM auth WHERE tg_id = ?', [tgId]);
        if (users.length === 0) {
            return res.json({ success: false, error: 'User not found' });
        }
        
        const currentUser = users[0];

        // 2. Check if user already used a referral code
        if (currentUser.referred_by) {
            return res.json({ success: false, error: 'You have already used a referral code' });
        }

        // 3. Check if they are using their own code
        if (currentUser.referral_code === referralCode) {
            return res.json({ success: false, error: 'You cannot use your own referral code' });
        }

        // 4. Find the referrer by code
        const [referrers] = await pool.execute('SELECT * FROM auth WHERE referral_code = ?', [referralCode]);
        if (referrers.length === 0) {
            return res.json({ success: false, error: 'Invalid referral code' });
        }

        const referrer = referrers[0];

        // 5. Update the user: set referred_by and add 20 ETB bonus
        const connection = await pool.getConnection();
        try {
            await connection.beginTransaction();

            await connection.execute(
                'UPDATE auth SET referred_by = ?, balance = balance + 20 WHERE tg_id = ?',
                [referrer.tg_id, tgId]
            );

            // Add alert to user
            await connection.execute(
                'INSERT INTO alerts (user_id, title, message, type) VALUES (?, ?, ?, ?)',
                [tgId, 'Referral Bonus', 'You received 20 ETB bonus for using a referral code!', 'success']
            );

            // Optional: Also give bonus to referrer? Prompt said "he will get bonus amount balance 20 etb"
            // For now, only the new user gets it, as decided. But we can send an alert to referrer.
            await connection.execute(
                'INSERT INTO alerts (user_id, title, message, type) VALUES (?, ?, ?, ?)',
                [referrer.tg_id, 'Referral Success', `User ${currentUser.first_name || 'Someone'} signed up using your code!`, 'success']
            );

            await connection.commit();
        } catch (err) {
            await connection.rollback();
            throw err;
        } finally {
            connection.release();
        }

        // Fetch updated balance to return
        const [updatedUsers] = await pool.execute('SELECT balance, referred_by FROM auth WHERE tg_id = ?', [tgId]);

        return res.json({ 
            success: true, 
            message: 'Referral code applied successfully! 20 ETB added.',
            newBalance: parseFloat(updatedUsers[0].balance),
            referred_by: updatedUsers[0].referred_by
        });

    } catch (err) {
        console.error('Apply referral error:', err);
        return res.json({ success: false, error: 'Internal server error' });
    }
});

export default router;
