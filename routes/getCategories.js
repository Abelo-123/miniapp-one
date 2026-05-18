import { Router } from 'express';
import pool from '../config/database.js';

const router = Router();

let cachedCategories = [];
let lastCacheTime = 0;
const CACHE_TTL_MS = 60 * 60 * 1000;

async function preloadCategories() {
    const apiKey = process.env.GODOFPANEL_API_KEY;
    if (!apiKey) return;
    
    try {
        let disabledServiceIds = new Set();
        try {
            const [disabledRows] = await pool.execute('SELECT service_id FROM service_custom WHERE is_enabled = 0');
            disabledRows.forEach(row => disabledServiceIds.add(row.service_id));
        } catch (e) { }
        
        let response, rawServices;
        for (let i = 0; i < 3; i++) {
            try {
                const params = new URLSearchParams({ key: apiKey, action: 'services' });
                response = await fetch('https://godofpanel.com/api/v2', {
                    method: 'POST',
                    headers: { 'User-Agent': 'PaxyoServer/2.0' },
                    body: params
                });
                if (response.ok) {
                    const data = await response.json();
                    if (Array.isArray(data)) {
                        rawServices = data;
                        break;
                    }
                }
            } catch (e) {}
            await new Promise(r => setTimeout(r, 1000));
        }

        if (Array.isArray(rawServices)) {
            const enabledServices = rawServices.filter(s => !disabledServiceIds.has(parseInt(s.service)));
            cachedCategories = [...new Set(enabledServices.map(s => s.category).filter(Boolean))];
            lastCacheTime = Date.now();
        }
    } catch(e) { }
}

preloadCategories();

const PLATFORM_KEYWORDS = {
    instagram: ['instagram', 'ig '],
    tiktok: ['tiktok', 'tik tok'],
    youtube: ['youtube', 'yt '],
    facebook: ['facebook', 'fb '],
    twitter: ['twitter', 'x.com', 'tweet'],
    telegram: ['telegram', 'tg '],
};

router.get('/', async (req, res) => {
    try {
        const forceRefresh = req.query.refresh === '1';
        const platform = req.query.platform || null;
        const apiKey = process.env.GODOFPANEL_API_KEY;

        if (!apiKey) return res.status(500).json({ error: 'GODOFPANEL_API_KEY not configured' });

        const now = Date.now();
        let allCategories = cachedCategories;

        let disabledServiceIds = new Set();
        try {
            const [disabledRows] = await pool.execute('SELECT service_id FROM service_custom WHERE is_enabled = 0');
            disabledRows.forEach(row => disabledServiceIds.add(row.service_id));
        } catch (e) { }

        if (forceRefresh || !allCategories || (now - lastCacheTime) > CACHE_TTL_MS) {
            let response, rawServices, lastProviderError = null;
            for (let i = 0; i < 3; i++) {
                try {
                    const params = new URLSearchParams({ key: apiKey, action: 'services' });
                    response = await fetch('https://godofpanel.com/api/v2', {
                        method: 'POST',
                        headers: { 'User-Agent': 'PaxyoServer/2.0' },
                        body: params
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        if (Array.isArray(data)) {
                            rawServices = data;
                            break;
                        } else if (data.error) {
                            lastProviderError = data.error;
                        }
                    }
                } catch (e) {}
                await new Promise(r => setTimeout(r, 1000));
            }

            if (!rawServices) {
                if (lastProviderError) return res.status(502).json({ error: lastProviderError });
                throw new Error('Invalid response format or timeout after 3 retries');
            }

            const enabledServices = rawServices.filter(s => !disabledServiceIds.has(parseInt(s.service)));
            allCategories = [...new Set(enabledServices.map(svc => svc.category).filter(Boolean))];

            cachedCategories = allCategories;
            lastCacheTime = now;
        }

        let result = allCategories;
        if (platform && platform !== 'top') {
            const keywords = PLATFORM_KEYWORDS[platform];
            if (platform === 'other') {
                const allMajorKeywords = Object.values(PLATFORM_KEYWORDS).flat();
                result = allCategories.filter(cat => !allMajorKeywords.some(kw => cat.toLowerCase().includes(kw)));
            } else if (keywords) {
                result = allCategories.filter(cat => keywords.some(kw => cat.toLowerCase().includes(kw)));
            }
        }

        return res.json({ success: true, categories: result, total: result.length, cached: (now - lastCacheTime) > 1000 });
    } catch (err) {
        if (cachedCategories && cachedCategories.length > 0) {
            return res.json({ success: true, categories: cachedCategories, total: cachedCategories.length, cached: true, stale: true });
        }
        return res.status(500).json({ error: 'Failed to fetch categories from provider' });
    }
});

export default router;