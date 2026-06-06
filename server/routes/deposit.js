/**
 * Deposit Handler — Chapa Payment Integration
 *
 * POST /api/deposit
 *
 * Handles two flows:
 *   1. INLINE SDK mode: Frontend provides tx_ref → just create DB row
 *   2. REDIRECT mode: No tx_ref → initialize Chapa API, return checkout_url
 *
 * Request body (JSON):
 *   { amount: number, initData: string, tx_ref?: string }
 *
 * Response:
 *   Inline:   { success: true, tx_ref, deposit_id }
 *   Redirect: { success: true, checkout_url, tx_ref }
 *
 * Replaces: deposit_handler.php
 */
import { Router } from 'express';
import crypto from 'crypto';
import pool from '../config/database.js';
import Chapa from '../lib/chapa.js';
import { getTelegramUserId } from '../lib/auth.js';

const router = Router();

const MIN_DEPOSIT = parseInt(process.env.MIN_DEPOSIT) || 10;
const MAX_DEPOSIT = parseInt(process.env.MAX_DEPOSIT) || 100000;

router.post('/', async (req, res) => {
    try {
        const { amount: rawAmount, initData, tx_ref: txRef, user_id, return_url } = req.body;
        const amount = parseFloat(rawAmount) || 0;

        // ─── Validate amount ─────────────────────────────────
        if (!amount || amount < MIN_DEPOSIT) {
            return res.json({ success: false, error: `Minimum deposit is ${MIN_DEPOSIT} ETB` });
        }
        if (amount > MAX_DEPOSIT) {
            return res.json({ success: false, error: `Maximum deposit is ${MAX_DEPOSIT.toLocaleString()} ETB` });
        }

        // ─── Authenticate user via Telegram initData (with local fallback) ─────────
        let tgId = getTelegramUserId(initData);
        if (!tgId) {
            tgId = user_id || 'unauth_local_user';
            console.warn(`[deposit] User not authenticated via Telegram. Using fallback ID: ${tgId}`);
        }

        // ─── Find or create user ─────────────────────────────
        let [users] = await pool.execute('SELECT * FROM auth WHERE tg_id = ?', [tgId]);
        let user = users[0];

        if (!user) {
            await pool.execute(
                "INSERT INTO auth (tg_id, balance, auth_provider, last_login) VALUES (?, 0.00, 'telegram', NOW())",
                [tgId]
            );
            [users] = await pool.execute('SELECT * FROM auth WHERE tg_id = ?', [tgId]);
            user = users[0];
        }

        // ═══ FLOW A: INLINE SDK MODE (tx_ref provided by frontend) ═══
        if (txRef) {
            // Check if tx_ref already exists (idempotent)
            const [existing] = await pool.execute('SELECT id FROM deposits WHERE tx_ref = ?', [txRef]);

            if (existing[0]) {
                return res.json({
                    success: true,
                    tx_ref: txRef,
                    deposit_id: existing[0].id,
                });
            }

            // Create a pending deposit record
        const [result] = await pool.execute(
            "INSERT INTO deposits (user_id, amount, tx_ref, status) VALUES (?, ?, ?, 'pending')",
            [tgId, amount, txRef]
        );

            if (result.insertId) {
                return res.json({
                    success: true,
                    tx_ref: txRef,
                    deposit_id: result.insertId,
                });
            } else {
                return res.json({ success: false, error: 'Failed to create deposit record' });
            }
        }

        // ═══ FLOW B: REDIRECT MODE (server generates tx_ref + calls Chapa/Telegram) ═══
        const generatedTxRef = `DEP-${tgId}-${Date.now()}-${crypto.randomBytes(4).toString('hex')}`;

        // Insert pending deposit
        await pool.execute(
            "INSERT INTO deposits (user_id, amount, tx_ref, status) VALUES (?, ?, ?, 'pending')",
            [tgId, amount, generatedTxRef]
        );

        let checkoutUrl = '';
        let success = false;
        let errorMsg = '';

        const providerToken = process.env.TELEGRAM_PAYMENT_PROVIDER_TOKEN;
        const botToken = process.env.BOT_TOKEN;

        if (providerToken && botToken) {
            console.log(`[deposit] Generating native Telegram invoice link using provider token...`);
            try {
                const tgRes = await fetch(`https://api.telegram.org/bot${botToken}/createInvoiceLink`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        title: 'Balance Deposit',
                        description: `Add ${amount} ETB to your Paxyo balance`,
                        payload: generatedTxRef,
                        provider_token: providerToken,
                        currency: 'ETB',
                        prices: [
                            { label: 'Deposit Amount', amount: Math.round(amount * 100) }
                        ]
                    })
                });
                const tgData = await tgRes.json();
                if (tgData.ok && tgData.result) {
                    checkoutUrl = tgData.result;
                    success = true;
                } else {
                    console.error('[deposit] Telegram createInvoiceLink error:', tgData);
                    errorMsg = tgData.description || 'Failed to generate Telegram invoice link';
                }
            } catch (err) {
                console.error('[deposit] Telegram API error:', err.message);
                errorMsg = `Telegram connection error: ${err.message}`;
            }
        }

        // If not processed via Telegram or failed, fall back to standard Chapa hosted redirect flow
        if (!success) {
            if (providerToken && botToken) {
                console.warn(`[deposit] Telegram invoice generation failed. Falling back to Chapa hosted URL... Error: ${errorMsg}`);
            }
            const chapa = new Chapa();
            const result = await chapa.initialize({
                amount,
                email: user.email || 'customer@paxyo.com',
                first_name: user.first_name || 'User',
                last_name: user.last_name || '',
                tx_ref: generatedTxRef,
                return_url: return_url,
            });

            if (result.success && result.data?.checkout_url) {
                checkoutUrl = result.data.checkout_url;
                success = true;
            } else {
                errorMsg = result.message || 'Failed to initialize Chapa payment';
            }
        }

        if (success && checkoutUrl) {
            // Save checkout_url in DB
            await pool.execute('UPDATE deposits SET checkout_url = ? WHERE tx_ref = ?', [
                checkoutUrl,
                generatedTxRef,
            ]);

            return res.json({
                success: true,
                checkout_url: checkoutUrl,
                tx_ref: generatedTxRef,
                tg_error: errorMsg || undefined
            });
        } else {
            return res.json({
                success: false,
                error: errorMsg || 'Payment initialization failed',
            });
        }
    } catch (err) {
        console.error('[deposit_handler] Error:', err);
        return res.json({ 
            success: false, 
            error: 'Database error',
            debug: err.message,
            code: err.code
        });
    }
});

export default router;
