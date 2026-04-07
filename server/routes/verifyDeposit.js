/**
 * Verify Deposit — Retry verification for pending deposits
 *
 * POST /api/verify-deposit
 *
 * Called by the frontend's retry logic when a deposit verification was pending.
 * Checks with Chapa API and credits balance if confirmed.
 *
 * Request body (JSON):
 *   { tx_ref: string, initData: string }
 *
 * Response:
 *   { success: true, new_balance } or { success: false, message }
 *
 * Replaces: verify_deposit.php
 */
import { Router } from 'express';
import pool from '../config/database.js';
import Chapa from '../lib/chapa.js';
import { getTelegramUserId } from '../lib/auth.js';

const router = Router();

router.post('/', async (req, res) => {
    const conn = await pool.getConnection();

    try {
        const { tx_ref: txRef, initData } = req.body;

        // ─── Authenticate user ──────────────────────────────
        const tgId = getTelegramUserId(initData);
        if (!tgId) {
            conn.release();
            return res.json({ success: false, error: 'User not authenticated' });
        }

        if (!txRef) {
            conn.release();
            return res.json({ success: false, error: 'Missing transaction reference' });
        }

        await conn.beginTransaction();

        // Lock deposit
        const [pendingDeposits] = await conn.execute(
            "SELECT * FROM deposits WHERE tx_ref = ? AND status = 'pending' FOR UPDATE",
            [txRef]
        );
        const deposit = pendingDeposits[0];

        if (!deposit) {
            await conn.rollback();

            // Check if already completed
            const [existingDeposits] = await conn.execute(
                'SELECT status FROM deposits WHERE tx_ref = ?',
                [txRef]
            );
            const existing = existingDeposits[0];

            if (existing && existing.status === 'success') {
                const [balRows] = await conn.execute(
                    'SELECT balance FROM auth WHERE tg_id = ?',
                    [tgId]
                );
                const balance = parseFloat(balRows[0]?.balance) || 0;

                conn.release();
                return res.json({
                    success: true,
                    new_balance: balance,
                    already_completed: true,
                });
            }

            conn.release();
            return res.json({ success: false, message: 'Deposit not found or already processed' });
        }

        // Verify with Chapa
        const chapa = new Chapa();
        const result = await chapa.verify(txRef);
        
        console.log(`[verify_deposit] Chapa Result for ${txRef}:`, JSON.stringify(result));

        // Check if truly successful — Chapa sometimes uses 'success' or 'paid'
        const chapaStatus = (result.data?.status ?? '').toLowerCase();

        if (result.success && (chapaStatus === 'success' || chapaStatus === 'paid')) {
            const verifiedAmount = parseFloat(result.data?.amount) || deposit.amount;
            const chapaRef = result.data?.reference || '';
            const responseJson = JSON.stringify(result.raw);

            console.log(`[verify_deposit] Success! Crediting ${verifiedAmount} to user ${deposit.user_id}`);

            // Update deposit
            await conn.execute(
                "UPDATE deposits SET status = 'success', chapa_tx_ref = ?, chapa_response = ?, completed_at = NOW() WHERE id = ?",
                [chapaRef, responseJson, deposit.id]
            );

            // Credit balance
            const [updateRes] = await conn.execute(
                'UPDATE auth SET balance = balance + ? WHERE tg_id = ?',
                [verifiedAmount, String(deposit.user_id)]
            );
            
            console.log(`[verify_deposit] Auth Update result:`, updateRes.affectedRows > 0 ? 'Success' : 'FAILED - No row updated');

            // Get new balance
            const [balRows] = await conn.execute(
                'SELECT balance FROM auth WHERE tg_id = ?',
                [String(deposit.user_id)]
            );
            const newBalance = parseFloat(balRows[0]?.balance) || 0;

            // Record transaction
            await conn.execute(
                `INSERT INTO transactions (user_id, type, amount, balance_after, reference_type, reference_id, description)
                 VALUES (?, 'deposit', ?, ?, 'deposit', ?, 'Chapa deposit (verified)')`,
                [String(deposit.user_id), verifiedAmount, newBalance, deposit.id]
            );

            await conn.commit();
            conn.release();

            return res.json({
                success: true,
                new_balance: newBalance,
            });
        } else {
            // Leave as pending — don't mark as failed so retry is possible
            await conn.commit();
            conn.release();

            // Extract the true status and message. If a transaction fails (e.g., Wrong PIN, Insufficient Funds),
            // Chapa might return { message: "Insufficient balance", status: "failed", data: null }
            const realStatus = result.data?.status || result.raw?.status || 'pending';
            const realMessage = result.data?.charge_message || result.data?.payment_message || result.message || result.raw?.message || 'Payment declined by bank or provider.';

            console.log(
                `[verify_deposit] Chapa Verification for ${txRef}: Status=${realStatus}, Msg=${realMessage} (Success: ${result.success})`
            );

            return res.json({
                success: false,
                message: `Payment status: ${realStatus}`,
                chapa_status: realStatus,
                bank_message: realMessage
            });
        }
    } catch (err) {
        try { await conn.rollback(); } catch {}
        conn.release();
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
