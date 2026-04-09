import crypto from 'crypto';

/**
 * Validates Telegram initData and returns the user ID if valid.
 *
 * @param {string} initData - URL-encoded initData from Telegram SDK
 * @returns {string|null} Telegram user ID, or null if invalid/not found
 */
export function getTelegramUserId(initData) {
    if (!initData) return null;

    try {
        const params = new URLSearchParams(initData);
        const hash = params.get('hash');
        params.delete('hash');
        
        // Sort keys alphabetically
        const dataCheckString = Array.from(params.entries())
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([key, value]) => `${key}=${value}`)
            .join('\n');

        // Generate HMAC using your Bot Token
        const secret = crypto.createHmac('sha256', 'WebAppData').update(process.env.BOT_TOKEN).digest();
        const calculatedHash = crypto.createHmac('sha256', secret).update(dataCheckString).digest('hex');

        if (hash !== calculatedHash) {
            console.warn('[auth] Invalid initData hash mismatch');
            return null;
        }
        
        const userStr = params.get('user');
        if (!userStr) return null;

        const userData = JSON.parse(userStr);
        return userData?.id ? String(userData.id) : null;
    } catch (err) {
        console.error('[auth] Error parsing initData:', err);
        return null;
    }
}

/**
 * Validates Telegram initData and returns the user object if valid.
 *
 * @param {string} initData - URL-encoded initData from Telegram SDK
 * @returns {Object|null} User object, or null if invalid/not found
 */
export function getTelegramUser(initData) {
    if (!initData) return null;

    try {
        const params = new URLSearchParams(initData);
        const hash = params.get('hash');
        params.delete('hash');
        
        // Sort keys alphabetically
        const dataCheckString = Array.from(params.entries())
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([key, value]) => `${key}=${value}`)
            .join('\n');

        // Generate HMAC using your Bot Token
        const secret = crypto.createHmac('sha256', 'WebAppData').update(process.env.BOT_TOKEN).digest();
        const calculatedHash = crypto.createHmac('sha256', secret).update(dataCheckString).digest('hex');

        if (hash !== calculatedHash) return null;

        const userStr = params.get('user');
        if (!userStr) return null;

        return JSON.parse(userStr);
    } catch {
        return null;
    }
}

