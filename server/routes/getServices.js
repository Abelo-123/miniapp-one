/**
 * Get Services — Direct Fetch from GodOfPanel
 *
 * GET /api/services
 *
 * Refreshes data directly from godofpanel.com using GODOFPANEL_API_KEY
 * Applies 'rate_multiplier' from settings to convert USD -> ETB.
 */
import { Router } from 'express';
import pool from '../config/database.js';

const router = Router();

// In-memory cache to prevent spamming GodOfPanel
let cachedServices = null;
let lastCacheTime = 0;
const CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

router.get('/', async (req, res) => {
    try {
        const forceRefresh = req.query.refresh === '1';
        const apiKey = process.env.GODOFPANEL_API_KEY;

        if (!apiKey) {
            return res.status(500).json({ error: 'GODOFPANEL_API_KEY not configured in backend .env' });
        }

        const now = Date.now();
        if (!forceRefresh && cachedServices && (now - lastCacheTime) < CACHE_TTL_MS) {
            return res.json(cachedServices);
        }

        // 1. Fetch raw services from GodOfPanel
        const response = await fetch(`https://godofpanel.com/api/v2?key=${apiKey}&action=services`);
        if (!response.ok) {
            throw new Error(`GodOfPanel returned ${response.status}`);
        }
        
        const rawServices = await response.json();
        
        if (!Array.isArray(rawServices)) {
            // GodOfPanel might return { error: "..." } if key is invalid
            if (rawServices.error) {
                console.error('[get_services] Provider Error:', rawServices.error);
                return res.status(502).json({ error: rawServices.error });
            }
            throw new Error('Invalid response format from provider');
        }

        // 2. Fetch rate multiplier from DB
        const [settingsRows] = await pool.execute(
            'SELECT setting_value FROM settings WHERE setting_key = "rate_multiplier"'
        );
        let rateMultiplier = 55.0; // Fallback default
        if (settingsRows.length > 0) {
            rateMultiplier = parseFloat(settingsRows[0].setting_value) || 55.0;
        }

        // 3. Fetch manual service adjustments from DB (average times, etc)
        // Table might not exist yet, we wrap in try-catch to not break completely if missing
        let adjustmentsMap = {};
        try {
            const [adjRows] = await pool.execute('SELECT service_id, average_time FROM service_adjustments');
            adjRows.forEach(row => {
                adjustmentsMap[row.service_id] = row.average_time;
            });
        } catch (dbErr) {
            console.log('[get_services] Note: service_adjustments table might be missing or empty. Skipping adjustments.');
        }

        // 4. Transform services
        const finalServices = rawServices.map(svc => {
            const numericRate = parseFloat(svc.rate) || 0;
            const finalRate = (numericRate * rateMultiplier).toFixed(2);
            
            return {
                service: parseInt(svc.service),
                name: svc.name,
                type: svc.type,
                category: svc.category,
                rate: finalRate,
                min: parseInt(svc.min),
                max: parseInt(svc.max),
                refill: svc.refill === true || svc.refill === 1 || svc.refill === '1',
                cancel: svc.cancel === true || svc.cancel === 1 || svc.cancel === '1',
                average_time: adjustmentsMap[svc.service] || 'Not specified'
            };
        });

        // Update Cache
        cachedServices = finalServices;
        lastCacheTime = now;

        return res.json(finalServices);
    } catch (err) {
        console.error('[get_services] Error:', err);
        
        // Fallback to cache if request fails but we have stale data
        if (cachedServices) {
            console.log('[get_services] Serving stale cache due to upstream error.');
            return res.json(cachedServices);
        }

        return res.status(500).json({ error: 'Failed to fetch services from provider' });
    }
});

export default router;
