import { useState, useMemo, useEffect } from 'react';
import { PLATFORMS } from '../../constants';
import { getServices } from '../../api';
import type { SocialPlatform } from '../../types';

interface Props {
    platform: SocialPlatform;
    onSelect: (category: string) => void;
    onClose: () => void;
}

/**
 * CategoryModal — self-contained modal that fetches service data directly.
 *
 * On mount it calls getServices(true) which hits localStorage cache first
 * (near-instant if cached), then falls back to API. This makes it completely
 * independent of AppContext's loading state.
 */
export function CategoryModal({ platform, onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');
    const [rawServices, setRawServices] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    // Fetch services directly on mount — localStorage cache makes this near-instant
    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        getServices(true)
            .then(data => {
                if (!cancelled) {
                    setRawServices(Array.isArray(data) ? data : []);
                    setLoading(false);
                }
            })
            .catch(() => {
                if (!cancelled) setLoading(false);
            });

        return () => { cancelled = true; };
    }, []);

    // Compute categories from the fetched services + selected platform
    const categories = useMemo(() => {
        if (rawServices.length === 0) return [];
        if (platform === 'top') return ['Top Services'];

        const platformDef = PLATFORMS.find(p => p.id === platform);
        if (!platformDef) return [];

        // Extract unique category names — works with both raw API and transformed data
        const allCategories = [...new Set(
            rawServices.map((s: any) => s.category as string).filter(Boolean)
        )];

        if (platform === 'other') {
            const majorKeywords = PLATFORMS
                .filter(p => p.id !== 'other' && p.id !== 'top')
                .flatMap(p => p.keywords);
            return allCategories.filter(cat => {
                const lower = cat.toLowerCase();
                return !majorKeywords.some(kw => lower.includes(kw));
            });
        }

        return allCategories.filter(cat => {
            const lower = cat.toLowerCase();
            return platformDef.keywords.some(kw => lower.includes(kw));
        });
    }, [rawServices, platform]);

    const filtered = useMemo(() => {
        if (!search.trim()) return categories;
        const q = search.toLowerCase();
        return categories.filter(c => c.toLowerCase().includes(q));
    }, [categories, search]);

    const screenH = typeof window !== 'undefined'
        ? (window.innerHeight || document.documentElement.clientHeight || 600)
        : 600;

    // ── ALL styles are inline to avoid WebView CSS bugs ──

    const overlayStyle: React.CSSProperties = {
        position: 'fixed',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        background: 'rgba(0,0,0,0.6)',
        zIndex: 9999,
        display: 'flex',
        alignItems: 'flex-end',
        justifyContent: 'center',
    };

    const sheetStyle: React.CSSProperties = {
        position: 'relative',
        width: '100%',
        maxWidth: 500,
        background: 'var(--tg-theme-bg-color, #1a1a2e)',
        borderRadius: '16px 16px 0 0',
        overflow: 'hidden',
    };

    const headerStyle: React.CSSProperties = {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: '16px 20px',
        borderBottom: '1px solid rgba(255,255,255,0.08)',
    };

    const searchBoxStyle: React.CSSProperties = {
        padding: '12px 16px',
    };

    const inputStyle: React.CSSProperties = {
        width: '100%',
        padding: '10px 14px',
        borderRadius: 10,
        border: '1px solid rgba(255,255,255,0.1)',
        background: 'rgba(255,255,255,0.05)',
        color: 'var(--tg-theme-text-color, #fff)',
        fontSize: 14,
        outline: 'none',
        boxSizing: 'border-box' as const,
    };

    const listStyle: React.CSSProperties = {
        position: 'relative',
        overflowY: 'auto',
        maxHeight: Math.max(screenH * 0.5, 250),
        minHeight: 150,
        WebkitOverflowScrolling: 'touch' as any,
        paddingBottom: 20,
        display: 'block',
    };

    const itemStyle: React.CSSProperties = {
        display: 'flex',
        alignItems: 'center',
        padding: '14px 16px',
        borderBottom: '1px solid rgba(255,255,255,0.04)',
        cursor: 'pointer',
        WebkitTapHighlightColor: 'transparent',
        color: 'var(--tg-theme-text-color, #fff)',
        fontSize: 14,
    };

    const emptyStyle: React.CSSProperties = {
        padding: '40px 20px',
        textAlign: 'center' as const,
        color: 'rgba(255,255,255,0.5)',
        fontSize: 14,
        lineHeight: 1.5,
    };

    const spinnerStyle: React.CSSProperties = {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '40px 20px',
        gap: 12,
    };

    return (
        <div style={overlayStyle} onClick={onClose}>
            <div style={sheetStyle} onClick={e => e.stopPropagation()}>
                {/* Header */}
                <div style={headerStyle}>
                    <span style={{ fontWeight: 600, fontSize: 16, color: 'var(--tg-theme-text-color, #fff)' }}>
                        Select Category
                    </span>
                    <button
                        onClick={onClose}
                        style={{
                            background: 'rgba(255,255,255,0.1)',
                            border: 'none',
                            width: 32,
                            height: 32,
                            borderRadius: '50%',
                            fontSize: 16,
                            color: '#888',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                        }}
                    >
                        ✕
                    </button>
                </div>

                {/* Search — only show when we have categories */}
                {!loading && categories.length > 0 && (
                    <div style={searchBoxStyle}>
                        <input
                            type="text"
                            placeholder="Search categories..."
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            style={inputStyle}
                        />
                    </div>
                )}

                {/* List */}
                <div style={listStyle} data-count={filtered.length}>
                    {loading ? (
                        <div style={spinnerStyle}>
                            <div style={{
                                width: 32,
                                height: 32,
                                border: '3px solid rgba(255,255,255,0.1)',
                                borderTopColor: 'var(--tg-theme-link-color, #6ab3f3)',
                                borderRadius: '50%',
                                animation: 'catModalSpin 0.8s linear infinite',
                            }} />
                            <span style={{ color: 'rgba(255,255,255,0.5)', fontSize: 14 }}>
                                Loading categories...
                            </span>
                            <style>{`@keyframes catModalSpin { to { transform: rotate(360deg); } }`}</style>
                        </div>
                    ) : categories.length === 0 ? (
                        <div style={emptyStyle}>
                            No categories available for this platform.
                        </div>
                    ) : filtered.length === 0 ? (
                        <div style={emptyStyle}>
                            No categories match your search.
                        </div>
                    ) : (
                        filtered.map(cat => (
                            <div key={cat} style={itemStyle} onClick={() => onSelect(cat)}>
                                <span style={{ marginRight: 12, fontSize: 18 }}>📂</span>
                                <span style={{ color: 'var(--tg-theme-text-color, #fff)' }}>{cat}</span>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
