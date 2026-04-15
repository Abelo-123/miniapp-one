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
    if (!initData || typeof initData !== 'string') return null;

    try {
        const params = new URLSearchParams(initData);
        const hash = params.get('hash');
        
        const userStr = params.get('user');
        const userData = userStr ? JSON.parse(userStr) : null;

        if (!hash) {
            if (!process.env.BOT_TOKEN) {
                return userData;
            }
            return null;
        }
        
        params.delete('hash');
        
        const dataCheckString = Array.from(params.entries())
            .sort(([a], [b]) => (a < b ? -1 : a > b ? 1 : 0))
            .map(([key, value]) => `${key}=${value}`)
            .join('\n');

        const botToken = process.env.BOT_TOKEN;

        if (!botToken) {
            console.warn('⚠️ WARNING: BOT_TOKEN is missing in your backend .env file!');
            console.warn('⚠️ Bypassing Telegram authentication. Accounts will work, but this is insecure for production.');
            return userData;
        }

        const secret = crypto.createHmac('sha256', 'WebAppData').update(botToken).digest();
        const calculatedHash = crypto.createHmac('sha256', secret).update(dataCheckString).digest('hex');

        if (hash !== calculatedHash) {
            console.error('❌ ERROR: Telegram validation failed! Your BOT_TOKEN in .env might be incorrect.');
            console.error('❌ Calculated:', calculatedHash, 'Provided:', hash);
            return null;
        }

        return userData;
    } catch (err) {
        console.error('Error in getTelegramUser:', err.message);
        return null;
    }
}