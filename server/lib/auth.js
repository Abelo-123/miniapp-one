import crypto from 'crypto';

/**
 * Validates Telegram initData and returns the user ID if valid.
 */
export function getTelegramUserId(initData) {
    const user = getTelegramUser(initData);
    return user?.id ? String(user.id) : null;
}

/**
 * Validates Telegram initData and returns the user object if valid.
 */
export function getTelegramUser(initData) {
    if (!initData || typeof initData !== 'string') {
        console.warn('[AuthDebug] No initData provided or invalid type:', typeof initData);
        return null;
    }

    try {
        console.log('[AuthDebug] --- START TELEGRAM SIGNATURE VALIDATION ---');
        console.log('[AuthDebug] Raw initData received:', initData);

        const params = new URLSearchParams(initData);
        const hash = params.get('hash');
        const userStr = params.get('user');
        const userData = userStr ? JSON.parse(userStr) : null;

        console.log('[AuthDebug] Extracted User Data:', userData);
        console.log('[AuthDebug] Extracted Hash:', hash);

        if (!hash) {
            console.warn('[AuthDebug] No hash parameter found in initData!');
            if (!process.env.BOT_TOKEN) {
                console.warn('[AuthDebug] BOT_TOKEN is missing in environment, returning parsed user data as fallback.');
                return userData;
            }
            return null;
        }
        
        params.delete('hash');
        
        const dataCheckString = Array.from(params.entries())
            .sort(([a], [b]) => (a < b ? -1 : a > b ? 1 : 0))
            .map(([key, value]) => `${key}=${value}`)
            .join('\n');

        console.log('[AuthDebug] Data Check String for HMAC:\n' + dataCheckString);

        const botToken = process.env.BOT_TOKEN;

        if (!botToken) {
            console.warn('[AuthDebug] ⚠️ WARNING: BOT_TOKEN is missing in your backend environment!');
            console.warn('[AuthDebug] ⚠️ Bypassing Telegram authentication. Accounts will work, but this is insecure for production.');
            return userData;
        }

        console.log('[AuthDebug] Bot Token configured (masked):', botToken.slice(0, 6) + '...' + botToken.slice(-4));

        const secret = crypto.createHmac('sha256', 'WebAppData').update(botToken).digest();
        const calculatedHash = crypto.createHmac('sha256', secret).update(dataCheckString).digest('hex');

        console.log('[AuthDebug] Calculated Hash:', calculatedHash);
        console.log('[AuthDebug] Provided Hash:  ', hash);

        if (hash !== calculatedHash) {
            console.error('[AuthDebug] ❌ ERROR: Telegram validation failed!');
            console.error('[AuthDebug] Check if the BOT_TOKEN matches your bot from @BotFather.');
            return null;
        }

        console.log('[AuthDebug] ✅ SUCCESS: Telegram signature verified successfully!');
        return userData;
    } catch (err) {
        console.error('[AuthDebug] Exception during getTelegramUser:', err.message);
        return null;
    }
}