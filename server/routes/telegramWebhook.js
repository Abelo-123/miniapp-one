/**
 * Telegram Webhook Handler — Native Payments Integration
 *
 * POST /api/telegram-webhook
 *
 * Handles pre_checkout_query (approves within 10s) and
 * successful_payment (credits user balance).
 */
import { Router } from 'express';
import pool from '../config/database.js';
import { processTransaction } from '../lib/wallet.js';
import { notifyDeposit } from '../lib/notify.js';

const router = Router();

router.post('/', async (req, res) => {
    try {
        const botToken = process.env.BOT_TOKEN;
        if (!botToken) {
            console.error('[telegram_webhook] ERROR: BOT_TOKEN is missing in environment variables!');
            return res.sendStatus(500);
        }

        const { pre_checkout_query, message } = req.body;

        // ─── 1. Handle Pre-Checkout Query ────────────────────────────
        if (pre_checkout_query) {
            const txRef = pre_checkout_query.invoice_payload;
            console.log(`[telegram_webhook] Received pre_checkout_query for ${txRef}`);

            // Verify deposit record exists and is pending
            const [rows] = await pool.execute(
                'SELECT id, status FROM deposits WHERE tx_ref = ?',
                [txRef]
            );
            const deposit = rows[0];

            let ok = true;
            let errorMessage = '';

            if (!deposit) {
                ok = false;
                errorMessage = 'Deposit transaction record not found.';
            } else if (deposit.status !== 'pending') {
                ok = false;
                errorMessage = 'This transaction has already been processed.';
            }

            // Respond to Telegram
            const response = await fetch(`https://api.telegram.org/bot${botToken}/answerPreCheckoutQuery`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pre_checkout_query_id: pre_checkout_query.id,
                    ok,
                    error_message: errorMessage || undefined
                })
            });

            const responseData = await response.json();
            console.log('[telegram_webhook] answerPreCheckoutQuery response:', responseData);

            return res.sendStatus(200);
        }

        // ─── 2. Handle Successful Payment ────────────────────────────
        if (message && message.successful_payment) {
            const payment = message.successful_payment;
            const txRef = payment.invoice_payload;
            const centsAmount = payment.total_amount;
            const verifiedAmount = centsAmount / 100; // Telegram payments are in smallest units (cents)
            const chargeId = payment.telegram_payment_charge_id || payment.provider_payment_charge_id || 'tg-charge';

            console.log(`[telegram_webhook] Payment successful for ${txRef}, Amount: ${verifiedAmount} ETB`);

            // Start transaction to safely credit balance
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
                    console.log(`[telegram_webhook] Deposit ${txRef} already successfully processed or not found.`);
                    await conn.rollback();
                    conn.release();
                    return res.sendStatus(200);
                }

                // Update deposit record
                const responseJson = JSON.stringify(payment);
                await conn.execute(
                    "UPDATE deposits SET status = 'success', chapa_tx_ref = ?, chapa_response = ?, completed_at = NOW() WHERE id = ?",
                    [chargeId, responseJson, deposit.id]
                );

                // Update balance and log transaction (handles 7% referral commission internally!)
                await processTransaction(
                    String(deposit.user_id),
                    'deposit',
                    verifiedAmount,
                    `Telegram native deposit (verified) - ${chargeId}`,
                    conn,
                    'deposit',
                    deposit.id
                );

                await conn.commit();

                // Async notification to admin
                pool.execute('SELECT first_name FROM auth WHERE tg_id = ?', [String(deposit.user_id)])
                    .then(([rows]) => {
                        const firstName = rows[0]?.first_name || 'User';
                        notifyDeposit({ uid: String(deposit.user_id), amount: verifiedAmount, uuid: firstName })
                            .catch(e => console.error('[telegram_webhook] Notify deposit error:', e));
                    }).catch(() => {});

                conn.release();
                console.log(`[telegram_webhook] Successfully credited ${verifiedAmount} ETB to user ${deposit.user_id}`);
                return res.sendStatus(200);
            } catch (err) {
                await conn.rollback();
                conn.release();
                console.error('[telegram_webhook] Database transaction error:', err);
                return res.sendStatus(500);
            }
        }

        // Unknown update type, just ack 200
        return res.sendStatus(200);
    } catch (err) {
        console.error('[telegram_webhook] Webhook error:', err);
        return res.sendStatus(500);
    }
});

export default router;
