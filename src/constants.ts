import type { SocialPlatform, Service } from './types';

// ─── API Configuration ───────────────────────────────────────
export const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';

// ─── Social Platforms ─────────────────────────────────────────
export interface PlatformDef {
    id: SocialPlatform;
    label: string;
    icon: string;       // emoji for now, can swap to SVG
    keywords: string[];  // used to filter categories
    placeholder: string; // link input placeholder
}

export const PLATFORMS: PlatformDef[] = [
    {
        id: 'instagram',
        label: 'Instagram',
        icon: '📸',
        keywords: ['instagram', 'ig '],
        placeholder: 'https://instagram.com/username',
    },
    {
        id: 'tiktok',
        label: 'TikTok',
        icon: '🎵',
        keywords: ['tiktok', 'tik tok'],
        placeholder: 'https://tiktok.com/@username/video/...',
    },
    {
        id: 'youtube',
        label: 'YouTube',
        icon: '▶️',
        keywords: ['youtube', 'yt '],
        placeholder: 'https://youtube.com/watch?v=...',
    },
    {
        id: 'facebook',
        label: 'Facebook',
        icon: '👤',
        keywords: ['facebook', 'fb '],
        placeholder: 'https://facebook.com/...',
    },
    {
        id: 'twitter',
        label: 'Twitter / X',
        icon: '🐦',
        keywords: ['twitter', 'x.com', 'tweet'],
        placeholder: 'https://x.com/username/status/...',
    },
    {
        id: 'telegram',
        label: 'Telegram',
        icon: '✈️',
        keywords: ['telegram', 'tg '],
        placeholder: 'https://t.me/channel_name',
    },
    {
        id: 'other',
        label: 'Other',
        icon: '🌐',
        keywords: [],      // catches everything not matched
        placeholder: 'https://...',
    },
    {
        id: 'top',
        label: 'Top',
        icon: '⭐',
        keywords: [],
        placeholder: 'https://...',
    },
];

// ─── Order Status ─────────────────────────────────────────────
export const STATUS_COLORS: Record<string, string> = {
    pending: 'var(--color-warning)',
    processing: 'var(--color-info)',
    in_progress: 'var(--color-info)',
    completed: 'var(--color-success)',
    cancelled: 'var(--color-danger)',
    partial: 'var(--color-warning)',
};

export const STATUS_LABELS: Record<string, string> = {
    pending: 'Pending',
    processing: 'Processing',
    in_progress: 'Processing',
    completed: 'Completed',
    cancelled: 'Cancelled',
    partial: 'Partial',
};

// ─── Category & Service → Placeholder mapping ─────────────────
export function getLinkPlaceholder(service: Service | null | undefined, platform: SocialPlatform): string {
    if (!service) {
        const def = PLATFORMS.find(p => p.id === platform);
        return def?.placeholder || 'https://...';
    }

    const searchString = `${service.category} ${service.name}`.toLowerCase();

    if (platform === 'telegram' || searchString.includes('telegram') || searchString.includes('tg ')) {
        if (searchString.includes('view') || searchString.includes('reaction') || searchString.includes('comment') || searchString.includes('post')) {
            return 'https://t.me/channel_name/1234';
        }
        return 'https://t.me/channel_name';
    }

    if (platform === 'instagram' || searchString.includes('instagram') || searchString.includes('ig ')) {
        if (searchString.includes('reel')) return 'https://instagram.com/reel/XXXXX/';
        if (searchString.includes('story') || searchString.includes('stories')) return 'https://instagram.com/stories/username/XXXXX/';
        if (searchString.includes('tv') || searchString.includes('igtv')) return 'https://instagram.com/tv/XXXXX/';
        if (searchString.includes('like') || searchString.includes('view') || searchString.includes('comment') || searchString.includes('save') || searchString.includes('reach') || searchString.includes('impression')) {
            return 'https://instagram.com/p/XXXXX/';
        }
        return 'https://instagram.com/username';
    }

    if (platform === 'tiktok' || searchString.includes('tiktok')) {
        if (searchString.includes('follower') || searchString.includes('profile')) return 'https://tiktok.com/@username';
        return 'https://tiktok.com/@username/video/XXXXX';
    }

    if (platform === 'youtube' || searchString.includes('youtube') || searchString.includes('yt ')) {
        if (searchString.includes('short')) return 'https://youtube.com/shorts/XXXXX';
        if (searchString.includes('subscriber') || searchString.includes('channel')) return 'https://youtube.com/@username';
        return 'https://youtube.com/watch?v=XXXXX';
    }

    if (platform === 'twitter' || searchString.includes('twitter') || searchString.includes('x.com')) {
        if (searchString.includes('follower')) return 'https://x.com/username';
        return 'https://x.com/username/status/XXXXX';
    }

    if (platform === 'facebook' || searchString.includes('facebook') || searchString.includes('fb ')) {
        if (searchString.includes('follower') || searchString.includes('page like') || searchString.includes('profile')) return 'https://facebook.com/pagename';
        if (searchString.includes('post') || searchString.includes('photo') || searchString.includes('video')) return 'https://facebook.com/pagename/posts/XXXXX';
        return 'https://facebook.com/...';
    }

    if (searchString.includes('follower') || searchString.includes('subscriber') || searchString.includes('member')) return 'Profile URL (e.g., https://...)';
    if (searchString.includes('like') || searchString.includes('view') || searchString.includes('comment')) return 'Post/Video URL (e.g., https://...)';

    const def = PLATFORMS.find(p => p.id === platform);
    return def?.placeholder || 'https://...';
}

// ─── Currency Formatting ──────────────────────────────────────
export function formatETB(amount: number | string): string {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 4,
        maximumFractionDigits: 4,
    }).format(num || 0) + ' ETB';
}

// ─── Misc ─────────────────────────────────────────────────────
export const HEARTBEAT_INTERVAL = 30_000;    // 30 seconds
export const SSE_RECONNECT_DELAY = 3_000;    // 3 seconds
export const REFILL_COOLDOWN_HOURS = 24;
export const TOAST_DURATION = 3_000;
export const QUANTITY_STEP = 10;
