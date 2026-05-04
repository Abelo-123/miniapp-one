/**
 * Verify Deposit — Retry verification for pending deposits
 *
 * POST /api/verify-deposit
 *
 * Fixed to prevent database deadlock by moving Chapa API call
 * OUTSIDE the database transaction.
 */

import { Router } from 'express';
import pool from '../config/database.js';
import Chapa from '../lib/chapa.js';
import { getTelegramUserId } from '../lib/auth.js';
import { processTransaction } from '../lib/wallet.js';
import { notifyDeposit } from '../lib/notify.js';

const router = Router();

router.post('/', async (req, res) => {
    try {
        const { tx_ref: txRef, initData, user_id } = req.body;

        // 1. Basic Validation
        if (!txRef) {
            return res.json({ success: false, error: 'Missing transaction reference' });
        }

        // 2. Authenticate user (with local fallback)
        let tgId = getTelegramUserId(initData);
        if (!tgId) {
            tgId = user_id || 'unauth_local_user';
        }

        // 3. Check local database first (idempotency & speed)
        const [initialCheck] = await pool.execute(
            'SELECT status, amount FROM deposits WHERE tx_ref = ?',
            [txRef]
        );
        const depositCheck = initialCheck[0];

        if (!depositCheck) {
            return res.json({ success: false, message: 'Deposit record not found in our system' });
        }

        // If already successful, return immediately
        if (depositCheck.status === 'success') {
            const [balRows] = await pool.execute('SELECT balance FROM auth WHERE tg_id = ?', [tgId]);
            return res.json({
                success: true,
                new_balance: parseFloat(balRows[0]?.balance) || 0,
                already_completed: true,
                message: 'Payment already verified and credited.'
            });
        }

        // 4. Verify with Chapa API
        const chapa = new Chapa();
        const result = await chapa.verify(txRef);
        
        console.log(`[verify_deposit] Chapa Result for ${txRef}:`, JSON.stringify(result));

        const chapaData = result.data || {};
        const chapaStatus = (chapaData.status || result.raw?.status || '').toLowerCase();
        
        // Terminal Success Statuses
        const isActuallySuccess = (chapaStatus === 'success' || chapaStatus === 'paid' || chapaStatus === 'completed');

        if (!isActuallySuccess) {
            // Check for Terminal Failure Statuses
            const isFailed = chapaStatus === 'failed' || chapaStatus.includes('reject') || chapaStatus.includes('cancel');
            
            if (isFailed) {
                await pool.execute("UPDATE deposits SET status = 'failed' WHERE tx_ref = ?", [txRef]);
                return res.json({
                    success: false,
                    chapa_status: 'failed',
                    message: chapaData.failure_reason || 'Payment was declined or cancelled.',
                    bank_message: chapaData.charge_message || 'Transaction failed.'
                });
            }

            // Otherwise, it's still pending
            return res.json({ 
                success: false, 
                chapa_status: 'pending', 
                message: 'Waiting for provider confirmation...', 
                bank_message: chapaData.charge_message || 'Transaction is still processing on the provider side.'
            });
        }

        // 5. Success Flow: Start transaction to safely credit balance
        const conn = await pool.getConnection();
        try {
            await conn.beginTransaction();

            // Lock the deposit row
            const [pendingDeposits] = await conn.execute(
                "SELECT * FROM deposits WHERE tx_ref = ? FOR UPDATE",
                [txRef]
            );
            const deposit = pendingDeposits[0];

            if (!deposit || deposit.status === 'success') {
                await conn.rollback();
                conn.release();
                const [balRows] = await pool.execute('SELECT balance FROM auth WHERE tg_id = ?', [tgId]);
                return res.json({
                    success: true,
                    new_balance: parseFloat(balRows[0]?.balance) || 0,
                    already_completed: true
                });
            }

            const verifiedAmount = parseFloat(chapaData.amount) || deposit.amount;
            const chapaRef = chapaData.reference || '';
            const responseJson = JSON.stringify(result.raw);

            // Update deposit record
            await conn.execute(
                "UPDATE deposits SET status = 'success', chapa_tx_ref = ?, chapa_response = ?, completed_at = NOW() WHERE id = ?",
                [chapaRef, responseJson, deposit.id]
            );

            // Update balance and log transaction
            const newBalance = await processTransaction(
                String(deposit.user_id),
                'deposit',
                verifiedAmount,
                `Chapa deposit (verified) - ${chapaRef}`,
                conn,
                'deposit',
                deposit.id
            );

            await conn.commit();
            
            // Async notification (don't wait for it)
            pool.execute('SELECT first_name FROM auth WHERE tg_id = ?', [String(deposit.user_id)])
                .then(([rows]) => {
                    const firstName = rows[0]?.first_name || 'User';
                    notifyDeposit({ uid: String(deposit.user_id), amount: verifiedAmount, uuid: firstName })
                        .catch(e => console.error('Notify deposit error:', e));
                }).catch(() => {});

            conn.release();

            return res.json({
                success: true,
                new_balance: newBalance,
                message: 'Payment verified and balance updated!'
            });
        } catch (err) {
            await conn.rollback();
            conn.release();
            throw err;
        }
    } catch (err) {
        console.error('[verify_deposit] Error:', err);
        return res.json({ 
            success: false, 
            message: 'System error during verification',
            debug: err.message,
            code: err.code
        });
    }
});

export default router;