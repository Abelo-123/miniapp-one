import pool from '../config/database.js';

/**
 * Process a balance transaction atomically.
 * Updates the user's balance and logs the transaction to the ledger.
 * 
 * @param {string} tgId - Telegram User ID
 * @param {string} type - Transaction type (deposit, order, refund, etc.)
 * @param {number} amount - Amount to add (positive) or subtract (negative)
 * @param {string} description - Human-readable description
 * @param {Object} conn - MySQL connection (must be within a transaction)
 * @param {string|null} refType - Optional reference type (e.g., 'order', 'deposit')
 * @param {number|null} refId - Optional reference ID from the related table
 * @returns {Promise<number>} The new balance
 */
export async function processTransaction(tgId, type, amount, description, conn, refType = null, refId = null) {
    // 1. Update Balance
    await conn.execute('UPDATE auth SET balance = balance + ? WHERE tg_id = ?', [amount, tgId]);
    
    // 2. Get New Balance
    const [rows] = await conn.execute('SELECT balance FROM auth WHERE tg_id = ?', [tgId]);
    if (rows.length === 0) throw new Error('User not found');
    const newBalance = parseFloat(rows[0].balance);
    
    // 3. Log to Ledger
    await conn.execute(
        `INSERT INTO transactions (user_id, type, amount, balance_after, reference_type, reference_id, description) 
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [tgId, type, amount, newBalance, refType, refId, description]
    );

    // 4. Reward 7% commission to referrer if this is a deposit
    if (type === 'deposit' && amount > 0) {
        try {
            const [userRows] = await conn.execute('SELECT referred_by FROM auth WHERE tg_id = ?', [tgId]);
            if (userRows.length > 0 && userRows[0].referred_by) {
                const referrerId = String(userRows[0].referred_by);
                const commission = amount * 0.07;

                // Update referrer referral_balance
                await conn.execute('UPDATE auth SET referral_balance = referral_balance + ? WHERE tg_id = ?', [commission, referrerId]);

                // Get referrer's new referral_balance
                const [refBalRows] = await conn.execute('SELECT referral_balance FROM auth WHERE tg_id = ?', [referrerId]);
                const refNewBal = refBalRows.length > 0 ? parseFloat(refBalRows[0].referral_balance) : 0;

                // Log transaction ledger for referrer
                await conn.execute(
                    `INSERT INTO transactions (user_id, type, amount, balance_after, reference_type, reference_id, description) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)`,
                    [referrerId, 'referral_commission', commission, refNewBal, 'referral_user', tgId, `7% referral commission from user #${tgId} deposit`]
                );

                // Add alert for referrer
                await conn.execute(
                    'INSERT INTO alerts (user_id, title, message, type) VALUES (?, ?, ?, ?)',
                    [referrerId, 'Referral Commission', `You earned ${commission.toFixed(2)} ETB (7%) from your referred friend's deposit!`, 'success']
                );
            }
        } catch (err) {
            console.error('Failed to reward referral commission:', err.message);
        }
    }
    
    return newBalance;
}
