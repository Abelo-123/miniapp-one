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

const router = Router();

router.post('/', async (req, res) => {
    try {
        const { tx_ref: txRef, initData } = req.body;

        // ─── Authenticate user ──────────────────────────────
        const tgId = getTelegramUserId(initData);
        if (!tgId) {
            return res.json({ success: false, error: 'User not authenticated' });
        }

        if (!txRef) {
            return res.json({ success: false, error: 'Missing transaction reference' });
        }

        // 1. Check if already completed WITHOUT locking first
        const [initialCheck] = await pool.execute(
            'SELECT status FROM deposits WHERE tx_ref = ?',
            [txRef]
        );
        const depositCheck = initialCheck[0];

        if (!depositCheck) {
            return res.json({ success: false, message: 'Deposit not found' });
        }

        if (depositCheck.status === 'success') {
            const [balRows] = await pool.execute(
                'SELECT balance FROM auth WHERE tg_id = ?',
                [tgId]
            );
            return res.json({
                success: true,
                new_balance: parseFloat(balRows[0]?.balance) || 0,
                already_completed: true,
            });
        }

        // 2. Make the slow Chapa API call OUTSIDE the database transaction/lock
        const chapa = new Chapa();
        const result = await chapa.verify(txRef);
        
        console.log(`[verify_deposit] Chapa Result for ${txRef}:`, JSON.stringify(result));

        const chapaStatus = (result.data?.status ?? '').toLowerCase();
        const isSuccess = result.success && (chapaStatus === 'success' || chapaStatus === 'paid');

        if (!isSuccess) {
            const realStatus = result.data?.status || result.raw?.status || 'pending';
            let realMessage = result.data?.charge_message || result.data?.payment_message || result.message || result.raw?.message || 'Payment declined by bank or provider.';

            // Prevent confusing "fetched successfully" message when it's actually just waiting for the user's PIN
            if (realMessage.toLowerCase().includes('fetched successfully') && realStatus === 'pending') {
                realMessage = 'Waiting for confirmation from your mobile provider...';
            }

            return res.json({
                success: false,
                message: `Payment status: ${realStatus}`,
                chapa_status: realStatus,
                bank_message: realMessage
            });
        }

        // 3. If Chapa says success, start transaction to safely credit balance
        const conn = await pool.getConnection();
        try {
            await conn.beginTransaction();

            // Lock deposit NOW
            const [pendingDeposits] = await conn.execute(
                "SELECT * FROM deposits WHERE tx_ref = ? FOR UPDATE",
                [txRef]
            );
            const deposit = pendingDeposits[0];

            if (!deposit) {
                await conn.rollback();
                conn.release();
                return res.json({ success: false, message: 'Deposit not found' });
            }

            // Double check if another process (like a webhook) already completed it while we were waiting for Chapa
            if (deposit.status === 'success') {
                await conn.rollback();
                
                const [balRows] = await conn.execute(
                    'SELECT balance FROM auth WHERE tg_id = ?',
                    [tgId]
                );
                conn.release();
                return res.json({
                    success: true,
                    new_balance: parseFloat(balRows[0]?.balance) || 0,
                    already_completed: true,
                });
            }

            const verifiedAmount = parseFloat(result.data?.amount) || deposit.amount;
            const chapaRef = result.data?.reference || '';
            const responseJson = JSON.stringify(result.raw);

            // Update deposit
            await conn.execute(
                "UPDATE deposits SET status = 'success', chapa_tx_ref = ?, chapa_response = ?, completed_at = NOW() WHERE id = ?",
                [chapaRef, responseJson, deposit.id]
            );

            // 4. Update Balance & Record Transaction
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
            conn.release();

            return res.json({
                success: true,
                new_balance: newBalance,
            });
        } catch (err) {
            await conn.rollback();
            conn.release();
            throw err; // caught by outer try-catch
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