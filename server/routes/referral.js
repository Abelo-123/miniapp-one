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

        let refersArray = [];
        try {
            if (referrer.refers) {
                refersArray = typeof referrer.refers === 'string' ? JSON.parse(referrer.refers) : referrer.refers;
            }
        } catch (e) {
            refersArray = [];
        }
        if (!refersArray.includes(tgId)) {
            refersArray.push(tgId);
        }

        // 5. Update the user: set referred_by and add 20 ETB bonus
        const connection = await pool.getConnection();
        try {
            await connection.beginTransaction();

            await connection.execute(
                'UPDATE auth SET referred_by = ?, balance = balance + 20 WHERE tg_id = ?',
                [referrer.tg_id, tgId]
            );

            await connection.execute(
                'UPDATE auth SET refers = ? WHERE tg_id = ?',
                [JSON.stringify(refersArray), referrer.tg_id]
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

// GET STATS
router.post('/stats', async (req, res) => {
    const { initData } = req.body;
    const tgId = getTelegramUserId(initData);
    if (!tgId) {
        return res.json({ success: false, error: 'Unauthorized' });
    }

    try {
        // 1. Get total commission earned
        const [totalEarnedRows] = await pool.execute(
            "SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'referral_commission'",
            [tgId]
        );
        const totalEarned = parseFloat(totalEarnedRows[0].total || 0);

        // 2. Get all referred users (users where referred_by = current user's tgId)
        const [referredUsers] = await pool.execute(
            "SELECT tg_id, username, first_name, last_name, last_login FROM auth WHERE referred_by = ?",
            [tgId]
        );

        const referredList = [];
        for (const u of referredUsers) {
            // Get count of deposits made by this referred user
            const [depositCountRows] = await pool.execute(
                "SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND type = 'deposit'",
                [u.tg_id]
            );
            const depositCount = parseInt(depositCountRows[0].count || 0);

            // Get total commission earned from this referred user
            const [commissionRows] = await pool.execute(
                "SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'referral_commission' AND reference_type = 'referral_user' AND reference_id = ?",
                [tgId, u.tg_id]
            );
            const commissionFromUser = parseFloat(commissionRows[0].total || 0);

            referredList.push({
                tg_id: u.tg_id,
                name: u.first_name || u.username || `User #${u.tg_id.slice(-4)}`,
                deposit_count: depositCount,
                commission_earned: commissionFromUser
            });
        }

        return res.json({
            success: true,
            totalEarned,
            referredList
        });
    } catch (err) {
        console.error('Fetch referral stats error:', err);
        return res.json({ success: false, error: 'Internal server error' });
    }
});

export default router;
