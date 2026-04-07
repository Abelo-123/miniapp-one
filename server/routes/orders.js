import { Router } from 'express';
import pool from '../config/database.js';
import { getTelegramUserId } from '../lib/auth.js';
import { notifyNewOrder } from '../lib/notify.js';

const router = Router();
const connectedClients = new Map();

router.get('/stream', (req, res) => {
    const { initData } = req.query;
    const tgId = getTelegramUserId(initData);
    if (!tgId) return res.status(401).end();

    res.writeHead(200, {
        'Content-Type': 'text/event-stream',
        'Cache-Control': 'no-cache, no-transform',
        'Connection': 'keep-alive',
        'X-Accel-Buffering': 'no'
    });
    if (res.flushHeaders) res.flushHeaders();
    res.write(`data: ${JSON.stringify({ type: 'CONNECTED' })}\n\n`);

    if (!connectedClients.has(tgId)) {
        connectedClients.set(tgId, new Set());
    }
    connectedClients.get(tgId).add(res);
    ensurePolling(); // Start polling loop if not already running

    req.on('close', () => {
        const set = connectedClients.get(tgId);
        if (set) {
            set.delete(res);
            if (set.size === 0) connectedClients.delete(tgId);
        }
    });
});

// placeOrder: POST /api/orders/place
router.post('/place', async (req, res) => {
    try {
        const { service, link, quantity, initData, answer_number, comments } = req.body;
        
        const tgId = getTelegramUserId(initData);
        if (!tgId) {
            return res.status(401).json({ success: false, error: 'User not authenticated' });
        }

        const apiKey = process.env.GODOFPANEL_API_KEY;
        if (!apiKey) return res.status(500).json({ success: false, error: 'Provider API key missing' });

        const conn = await pool.getConnection();
        try {
            await conn.beginTransaction();
            
            // 1. Get rate multiplier
            const [settingsRows] = await conn.execute('SELECT setting_value FROM settings WHERE setting_key = "rate_multiplier"');
            const rateMultiplier = settingsRows.length > 0 ? parseFloat(settingsRows[0].setting_value) : 55.0;

            // 2. Lock user row to prevent race conditions
            const [userRows] = await conn.execute('SELECT * FROM auth WHERE tg_id = ? FOR UPDATE', [tgId]);
            const user = userRows[0];
            if (!user) {
                await conn.rollback();
                return res.json({ success: false, error: 'User not found' });
            }

            // 3. Fetch specific service from GodOfPanel
            // Actually, we should fetch services, but since GOP API is slow and we don't know the rate of this single service easily,
            // We can fetch from GOP: action=services
            const gopRes = await fetch(`https://godofpanel.com/api/v2?key=${apiKey}&action=services`);
            const allServices = await gopRes.json();
            const serviceData = allServices.find(s => parseInt(s.service) === parseInt(service));

            if (!serviceData) {
                await conn.rollback();
                return res.json({ success: false, error: 'Service not found or unavailable' });
            }

            // Calculate cost
            const unitRateUsd = parseFloat(serviceData.rate);
            const totalCostUsd = unitRateUsd * (quantity / 1000);
            const totalCostEtb = totalCostUsd * rateMultiplier;

            if (parseFloat(user.balance) < totalCostEtb) {
                await conn.rollback();
                return res.json({ success: false, error: 'Insufficient balance' });
            }

            // 4. Place order to GodOfPanel
            const orderParams = new URLSearchParams({
                key: apiKey,
                action: 'add',
                service: service.toString(),
                link: link,
                quantity: quantity.toString()
            });
            if (comments) orderParams.append('comments', comments);
            if (answer_number) orderParams.append('answer_number', answer_number.toString());

            const orderRes = await fetch('https://godofpanel.com/api/v2', {
                method: 'POST',
                body: orderParams
            });
            const orderData = await orderRes.json();

            if (orderData.error) {
                await conn.rollback();
                return res.json({ success: false, error: orderData.error });
            }

            const providerOrderId = orderData.order;

            // 4.5. Verify order was placed successfully with GodOfPanel
            let verifyAttempts = 0;
            let orderVerified = false;
            let finalOrderStatus = null;
            
            while (verifyAttempts < 3 && !orderVerified) {
                await new Promise(r => setTimeout(r, 500)); // Wait 500ms
                
                const checkRes = await fetch(`https://godofpanel.com/api/v2?key=${apiKey}&action=status&order=${providerOrderId}`);
                const statusData = await checkRes.json();
                
                if (statusData && statusData.status) {
                    finalOrderStatus = statusData.status;
                    if (['pending', 'processing', 'inprogress', 'completed'].includes(finalOrderStatus.toLowerCase())) {
                        orderVerified = true;
                    }
                }
                verifyAttempts++;
            }

            if (!orderVerified) {
                console.log('[place_order] Warning: Could not verify order with provider, but order was placed');
            }

            // 5. Update user balance
            await conn.execute('UPDATE auth SET balance = balance - ? WHERE tg_id = ?', [totalCostEtb, tgId]);

            // Get new balance
            const [newBalRows] = await conn.execute('SELECT balance FROM auth WHERE tg_id = ?', [tgId]);
            const newBalanceStr = newBalRows[0].balance;

            // 6. Insert Order into DB
            const [insertRes] = await conn.execute(
                `INSERT INTO orders 
                 (user_id, service_id, service_name, link, target_link, quantity, api_order_id, charge, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())`,
                [tgId, service, serviceData.name, link, link, quantity, providerOrderId, totalCostEtb]
            );

            // 7. Log Transaction
            await conn.execute(
                `INSERT INTO transactions 
                 (user_id, type, amount, balance_after, reference_type, reference_id, description)
                 VALUES (?, 'order', ?, ?, 'order', ?, 'Placed Order #${insertRes.insertId}')`,
                [tgId, -totalCostEtb, newBalanceStr, insertRes.insertId]
            );

            await conn.commit();

            const dbId = insertRes.insertId;
            const newBalance = parseFloat(newBalanceStr);

            // Notify admin bot about new order
            console.log('[orders] Calling notifyNewOrder with:', { uid: tgId, uuid: user.username || user.first_name || 'User', service: serviceData.name, order: dbId.toString(), amount: totalCostEtb.toString() });
            notifyNewOrder({
                uid: tgId,
                uuid: user.username || user.first_name || 'User',
                service: serviceData.name,
                order: dbId.toString(),
                amount: totalCostEtb.toString()
            });

            // Register in the in-memory tracker → polling picks it up on next tick
            trackOrder(providerOrderId, { dbId, userId: tgId, charge: totalCostEtb, quantity, status: 'pending' });
            ensurePolling();

            // ── SSE: Instantly broadcast ORDER_PLACED to connected clients ──
            const userConnections = connectedClients.get(tgId);
            if (userConnections) {
                const placedPayload = JSON.stringify({
                    type: 'ORDER_PLACED',
                    order: {
                        id: dbId,
                        api_order_id: providerOrderId,
                        service_id: parseInt(service),
                        service_name: serviceData.name,
                        link,
                        quantity: parseInt(quantity),
                        charge: totalCostEtb,
                        status: 'pending',
                        remains: parseInt(quantity),
                        start_count: 0,
                        created_at: new Date().toISOString(),
                    },
                    new_balance: newBalance,
                });
                for (const client of userConnections) {
                    client.write(`data: ${placedPayload}\n\n`);
                }
            }

            return res.json({
                success: true,
                order_id: dbId.toString(),
                api_order_id: providerOrderId.toString(),
                new_balance: newBalance,
                verified: orderVerified,
                provider_status: finalOrderStatus,
            });
        } catch (err) {
            await conn.rollback();
            console.error('[place_order]', err);
            return res.status(500).json({ success: false, error: `Database error: ${err.message}` });
        } finally {
            conn.release();
        }
    } catch (err) {
        console.error('[place_order]', err);
        return res.status(500).json({ success: false, error: 'System error' });
    }
});

// getOrders
router.post('/list', async (req, res) => {
    const { initData } = req.body;
    const tgId = getTelegramUserId(initData);
    if (!tgId) return res.status(401).json({ error: 'Not authenticated' });

    try {
        const [rows] = await pool.execute(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 100',
            [tgId]
        );
        return res.json({ success: true, orders: rows });
    } catch (err) {
        console.error(err);
        return res.json({ success: false, orders: [] });
    }
});

// checkOrderStatus
router.post('/status', async (req, res) => {
    const { initData } = req.body;
    const tgId = getTelegramUserId(initData);
    if (!tgId) return res.status(401).json({ error: 'Not authenticated' });

    try {
        // Find pending/in_progress orders for this user
        const [orders] = await pool.execute(
            "SELECT id, api_order_id, charge, quantity, status FROM orders WHERE user_id = ? AND status IN ('pending', 'in_progress', 'processing')",
            [tgId]
        );

        if (orders.length === 0) return res.json({ success: true, updated: [] });

        const apiKey = process.env.GODOFPANEL_API_KEY;
        const reqOrderIds = orders.map(o => o.api_order_id).join(',');
        
        const gopRes = await fetch(`https://godofpanel.com/api/v2?key=${apiKey}&action=status&orders=${reqOrderIds}`);
        const statusMap = await gopRes.json();

        const updated = [];
        for (const order of orders) {
            const providerStatus = statusMap[order.api_order_id];
            if (providerStatus && providerStatus.status) {
                const newStatus = providerStatus.status.toLowerCase().replace(/\s+/g, '_');

                if (order.status !== newStatus) {
                    let refundAmt = 0;
                    if (['canceled', 'cancelled', 'refunded', 'fail', 'failed'].includes(newStatus)) {
                        refundAmt = parseFloat(order.charge);
                    } else if (newStatus === 'partial') {
                        const remains = parseInt(providerStatus.remains || 0);
                        const quantity = parseInt(order.quantity);
                        if (remains > 0 && quantity > 0) {
                            refundAmt = (remains / quantity) * parseFloat(order.charge);
                        }
                    }

                    if (refundAmt > 0) {
                        const conn = await pool.getConnection();
                        try {
                            await conn.beginTransaction();
                            await conn.execute('UPDATE auth SET balance = balance + ? WHERE tg_id = ?', [refundAmt, tgId]);
                            const [newBalRows] = await conn.execute('SELECT balance FROM auth WHERE tg_id = ?', [tgId]);
                            await conn.execute(
                                `INSERT INTO transactions (user_id, type, amount, balance_after, reference_type, reference_id, description)
                                 VALUES (?, 'refund', ?, ?, 'order_refund', ?, ?)`,
                                [tgId, refundAmt, newBalRows[0].balance, order.id, newStatus === 'partial' ? `Partial Refund for Order #${order.id}` : `Refund for Order #${order.id}`]
                            );
                            await conn.commit();
                        } catch (e) {
                            await conn.rollback();
                            console.error('[refund_tx]', e);
                        } finally {
                            conn.release();
                        }
                    }
                }

                await pool.execute('UPDATE orders SET status = ?, start_count = ?, remains = ? WHERE id = ?', 
                    [newStatus, providerStatus.start_count || 0, providerStatus.remains || 0, order.id]);
                updated.push({ id: order.id, status: newStatus });
            }
        }

        return res.json({ success: true, updated });
    } catch (err) {
        console.error(err);
        return res.json({ success: false, error: 'Sync failed' });
    }
});

// requestRefill
router.post('/refill', async (req, res) => {
    const { initData, order_id } = req.body;
    const tgId = getTelegramUserId(initData);
    if (!tgId) return res.status(401).json({ error: 'Not authenticated' });

    try {
        // Find provider order ID
        const [orders] = await pool.execute('SELECT api_order_id FROM orders WHERE id = ? AND user_id = ?', [order_id, tgId]);
        if (!orders[0]) return res.json({ success: false, message: 'Order not found' });

        const apiKey = process.env.GODOFPANEL_API_KEY;
        const gopRes = await fetch(`https://godofpanel.com/api/v2?key=${apiKey}&action=refill&order=${orders[0].api_order_id}`);
        const refillData = await gopRes.json();

        if (refillData.error) return res.json({ success: false, message: refillData.error });
        return res.json({ success: true, message: 'Refill requested' });
    } catch (err) {
        return res.json({ success: false, message: 'Failed to request refill' });
    }
});

// ═══════════════════════════════════════════════════════════════
// ULTRA-LEAN POLLING ENGINE
// ─ In-memory order tracker: zero DB queries per tick
// ─ Single batched GodOfPanel request per tick
// ─ 3-second adaptive loop (idle when no pending orders)
// ═══════════════════════════════════════════════════════════════

// In-memory tracker: apiOrderId → { dbId, userId, charge, quantity, status }
const pendingOrders = new Map();

// Register a new order into the tracker (called after placement)
function trackOrder(apiOrderId, { dbId, userId, charge, quantity, status }) {
    pendingOrders.set(String(apiOrderId), { dbId, userId, charge: parseFloat(charge), quantity: parseInt(quantity), status });
}

// Remove terminal orders from tracker
function untrackOrder(apiOrderId) {
    pendingOrders.delete(String(apiOrderId));
}

const TERMINAL_STATUSES = new Set(['completed', 'canceled', 'cancelled', 'refunded', 'fail', 'failed', 'partial']);

// Hydrate tracker from DB on boot (catch orders from before restart)
(async () => {
    try {
        const [rows] = await pool.execute(
            "SELECT id, api_order_id, user_id, charge, quantity, status FROM orders WHERE status IN ('pending', 'in_progress', 'processing')"
        );
        for (const r of rows) {
            trackOrder(r.api_order_id, { dbId: r.id, userId: r.user_id, charge: r.charge, quantity: r.quantity, status: r.status });
        }
        if (rows.length > 0) console.log(`[Poller] Hydrated ${rows.length} pending order(s) from DB`);
    } catch (e) {
        console.error('[Poller] Boot hydration failed:', e.message);
    }
})();

// ─── Main Polling Loop ──────────────────────────────────────
let pollTimer = null;

async function pollTick() {
    if (pendingOrders.size === 0) return;

    // Collect only orders whose users are currently connected via SSE
    const batch = [];
    for (const [apiId, info] of pendingOrders) {
        if (connectedClients.has(info.userId)) {
            batch.push({ apiId, ...info });
        }
    }

    if (batch.length === 0) return;

    const apiKey = process.env.GODOFPANEL_API_KEY;
    if (!apiKey) return;

    try {
        const ids = batch.map(o => o.apiId).join(',');
        const res = await fetch(`https://godofpanel.com/api/v2?key=${apiKey}&action=status&orders=${ids}`);
        const statusMap = await res.json();

        if (statusMap.error) return; // Rate limit — skip this tick silently

        for (const order of batch) {
            const info = statusMap[order.apiId];
            if (!info || !info.status) continue;

            const newStatus = info.status.toLowerCase().replace(/\s+/g, '_');
            if (newStatus === order.status) continue; // No change

            // ── Status changed! ──

            // 1. Calculate refund if needed
            let refundAmt = 0;
            if (['canceled', 'cancelled', 'refunded', 'fail', 'failed'].includes(newStatus)) {
                refundAmt = order.charge;
            } else if (newStatus === 'partial') {
                const remains = parseInt(info.remains || 0);
                if (remains > 0 && order.quantity > 0) {
                    refundAmt = (remains / order.quantity) * order.charge;
                }
            }

            // 2. Process refund in DB (only if needed)
            if (refundAmt > 0) {
                const conn = await pool.getConnection();
                try {
                    await conn.beginTransaction();
                    await conn.execute('UPDATE auth SET balance = balance + ? WHERE tg_id = ?', [refundAmt, order.userId]);
                    const [balRows] = await conn.execute('SELECT balance FROM auth WHERE tg_id = ?', [order.userId]);
                    await conn.execute(
                        `INSERT INTO transactions (user_id, type, amount, balance_after, reference_type, reference_id, description)
                         VALUES (?, 'refund', ?, ?, 'order_refund', ?, ?)`,
                        [order.userId, refundAmt, balRows[0].balance, order.dbId,
                         newStatus === 'partial' ? `Partial Refund #${order.dbId}` : `Refund #${order.dbId}`]
                    );
                    await conn.commit();
                } catch (e) {
                    await conn.rollback();
                } finally {
                    conn.release();
                }
            }

            // 3. Update DB (single lightweight UPDATE)
            await pool.execute(
                'UPDATE orders SET status = ?, start_count = ?, remains = ? WHERE id = ?',
                [newStatus, info.start_count || 0, info.remains || 0, order.dbId]
            );

            // 4. Push to SSE
            const clients = connectedClients.get(order.userId);
            if (clients) {
                const payload = JSON.stringify({
                    type: 'ORDER_UPDATED',
                    order: {
                        id: order.dbId,
                        api_order_id: parseInt(order.apiId),
                        status: newStatus,
                        start_count: info.start_count || 0,
                        remains: info.remains || 0,
                    },
                    refunded: refundAmt > 0,
                });
                for (const c of clients) c.write(`data: ${payload}\n\n`);
            }

            // 5. Update in-memory tracker
            if (TERMINAL_STATUSES.has(newStatus)) {
                untrackOrder(order.apiId);
            } else {
                pendingOrders.get(order.apiId).status = newStatus;
            }
        }
    } catch (err) {
        // Network error — silently skip, next tick will retry
    }
}

// Adaptive loop: 3s when active, stops cleanly when nothing to track
function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(async () => {
        if (pendingOrders.size === 0 && connectedClients.size === 0) {
            clearInterval(pollTimer);
            pollTimer = null;
            return;
        }
        await pollTick();
    }, 3000);
}

// Auto-start polling when SSE clients connect or orders are placed
function ensurePolling() {
    if (!pollTimer) startPolling();
}

// ─── Keepalive (lightweight comment ping) ───────────────────
setInterval(() => {
    for (const [, clients] of connectedClients) {
        for (const c of clients) c.write(': keepalive\n\n');
    }
}, 25000);

export { trackOrder, ensurePolling };
export default router;

