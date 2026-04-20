import { Router } from 'express';
import pool from '../config/database.js';

const router = Router();

const PLATFORM_KEYWORDS = {
    instagram: ['instagram', 'ig '],
    tiktok: ['tiktok', 'tik tok'],
    youtube: ['youtube', 'yt '],
    facebook: ['facebook', 'fb '],
    twitter: ['twitter', 'x.com', 'tweet'],
    telegram: ['telegram', 'tg '],
};

function determinePlatform(category) {
    if (!category) return 'other';
    const lower = category.toLowerCase();
    for (const [platform, keywords] of Object.entries(PLATFORM_KEYWORDS)) {
        if (keywords.some(kw => lower.includes(kw))) {
            return platform;
        }
    }
    return 'other';
}

// In-memory cache holds RAW services now to prevent double-multiplying rates
let cachedServices = [];
let lastCacheTime = 0;
const CACHE_TTL_MS = 60 * 60 * 1000; // 1 hour

router.get('/', async (req, res) => {
    res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    
    const forceRefresh = req.query.refresh === '1';
    const reqCategory = req.query.category || null;
    const reqIds = req.query.ids ? req.query.ids.split(',').map(id => parseInt(id, 10)) : null;
    const apiKey = process.env.GODOFPANEL_API_KEY;

    let processServices;

    try {
        if (!apiKey) {
            return res.status(500).json({ error: 'GODOFPANEL_API_KEY not configured in backend .env' });
        }

        // Get fresh DB settings for multiplier and disabled services
        let rateMultiplier = 55.0;
        try {
            const [settingsRows] = await pool.execute('SELECT setting_value FROM settings WHERE setting_key = "rate_multiplier"');
            if (settingsRows.length > 0) rateMultiplier = parseFloat(settingsRows[0].setting_value) || 55.0;
        } catch (e) { /* ignore DB error */ }

        let disabledServiceIds = new Set();
        try {
            const [disabledRows] = await pool.execute('SELECT service_id FROM service_custom WHERE is_enabled = 0');
            disabledRows.forEach(row => disabledServiceIds.add(row.service_id));
        } catch (e) { }

        let adjustmentsMap = {};
        try {
            const [adjRows] = await pool.execute('SELECT service_id, average_time FROM service_adjustments');
            adjRows.forEach(row => { adjustmentsMap[row.service_id] = row.average_time; });
        } catch (e) { }

        // Centralized processor applies formatting dynamically so cache doesn't bake in old rates
        processServices = (services) => {
            return services
                .filter(svc => !disabledServiceIds.has(parseInt(svc.service)))
                .map(svc => {
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
                        average_time: adjustmentsMap[svc.service] || svc.average_time || 'Not specified',
                        platform_id: determinePlatform(svc.category)
                    };
                });
        };

        const now = Date.now();
        let resultData = [];

        // --- FAST CACHE SERVE ---
        if (!forceRefresh && cachedServices && cachedServices.length > 0 && (now - lastCacheTime) < CACHE_TTL_MS) {
            resultData = processServices(cachedServices);
        } else {
            // --- DIRECT FETCH (POST required by most SMM panels) ---
            let response, rawServices, lastProviderError = null;
            for (let i = 0; i < 3; i++) {
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 30000);
                    
                    const params = new URLSearchParams({ key: apiKey, action: 'services' });
                    
                    response = await fetch('https://godofpanel.com/api/v2', {
                        method: 'POST',
                        headers: { 'User-Agent': 'PaxyoServer/2.0' },
                        body: params,
                        signal: controller.signal
                    });
                    clearTimeout(timeoutId);

                    if (response.ok) {
                        const data = await response.json();
                        if (Array.isArray(data)) {
                            rawServices = data;
                            break;
                        } else if (data.error) {
                            lastProviderError = data.error;
                        }
                    } else {
                        lastProviderError = `HTTP Error ${response.status}`;
                    }
                } catch (e) {
                    lastProviderError = e.message;
                }
                await new Promise(r => setTimeout(r, 1500)); 
            }

            if (!rawServices || rawServices.length === 0) {
                throw new Error(lastProviderError || 'GodOfPanel API failed after 3 retries.');
            }

            cachedServices = rawServices;
            lastCacheTime = now;
            resultData = processServices(rawServices);
        }

        // --- FILTER FINAL RESPONSE ---
        if (reqCategory === 'Top Services') {
            let topServicesIds = '';
            try {
                const [settingRows] = await pool.execute('SELECT setting_value FROM settings WHERE setting_key = "top_services_ids"');
                if (settingRows.length > 0) topServicesIds = settingRows[0].setting_value || '';
            } catch (e) {}
            const recommendedIds = topServicesIds.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n));
            resultData = resultData.filter(s => recommendedIds.includes(s.service));
        } else if (reqCategory) {
            resultData = resultData.filter(s => s.category === reqCategory);
        }

        if (reqIds) {
            resultData = resultData.filter(s => reqIds.includes(s.service));
        }

        return res.json(resultData);

    } catch (err) {
        console.error('[get_services] Error:', err.message);
        
        // Stale Cache Fallback
        if (cachedServices && cachedServices.length > 0 && processServices) {
            console.log('[get_services] Serving stale cache due to upstream error.');
            let resultData = processServices(cachedServices);
            
            if (reqCategory === 'Top Services') {
                let topServicesIds = '';
                try {
                    const [settingRows] = await pool.execute('SELECT setting_value FROM settings WHERE setting_key = "top_services_ids"');
                    if (settingRows.length > 0) topServicesIds = settingRows[0].setting_value || '';
                } catch (e) {}
                const recommendedIds = topServicesIds.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n));
                resultData = resultData.filter(s => recommendedIds.includes(s.service));
            } else if (reqCategory) {
                resultData = resultData.filter(s => s.category === reqCategory);
            }
            if (reqIds) resultData = resultData.filter(s => reqIds.includes(s.service));
            return res.json(resultData);
        }

        return res.status(500).json({ error: `GodOfPanel Error: ${err.message}` });
    }
});

export default router;