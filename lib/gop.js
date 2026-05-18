/**
 * GodOfPanel (GOP) API Wrapper
 * 
 * Centralized fetcher with an intelligent Cache-Layer.
 * Prevents waiting for slow API responses by serving stale data if needed.
 */

let cache = { services: [], timestamp: 0 };

/**
 * Fetches services from GodOfPanel with caching.
 * Cache remains valid for 10 minutes. If the API is down or slow,
 * it returns the last successful fetch (stale-while-revalidate pattern).
 * 
 * @returns {Promise<Array>} List of services
 */
export async function getServicesCached() {
    const now = Date.now();
    const CACHE_TTL = 600000; // 10 minutes

    // 1. Return fresh cache if available
    if (cache.services.length > 0 && (now - cache.timestamp) < CACHE_TTL) {
        return cache.services;
    }

    const apiKey = process.env.GODOFPANEL_API_KEY;
    if (!apiKey) {
        console.error('[gop] GODOFPANEL_API_KEY is missing');
        return cache.services;
    }

    let rawServices = null;
    let lastProviderError = null;

    // 2. Try to fetch from provider up to 3 times
    for (let i = 0; i < 3; i++) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout

            const params = new URLSearchParams({ key: apiKey, action: 'services' });

            const res = await fetch('https://godofpanel.com/api/v2', {
                method: 'POST',
                headers: { 'User-Agent': 'PaxyoServer/2.0' },
                body: params,
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            if (res.ok) {
                const data = await res.json();
                if (Array.isArray(data)) {
                    rawServices = data;
                    break; // Success! Break out of the retry loop.
                } else if (data.error) {
                    lastProviderError = data.error;
                }
            } else {
                lastProviderError = `HTTP Status: ${res.status}`;
            }
        } catch (e) {
            lastProviderError = e.message;
            console.error(`[gop] Fetch attempt ${i + 1} failed:`, e.message);
        }
        
        // Wait 1 second before retrying
        await new Promise(r => setTimeout(r, 1000));
    }

    if (rawServices && rawServices.length > 0) {
        cache = { services: rawServices, timestamp: now };
        return rawServices;
    } else {
        console.error('[gop] All 3 fetch attempts failed. Last error:', lastProviderError);
    }

    // 3. Fallback to stale cache if API completely failed
    return cache.services;
}

/**
 * Force clear the cache
 */
export function clearGopCache() {
    cache = { services: [], timestamp: 0 };
}