import { useState, useMemo, useEffect } from 'react';
import { useApp } from '../../context/AppContext';
import { PLATFORMS } from '../../constants';

interface Props {
    categories: string[];
    onSelect: (category: string) => void;
    onClose: () => void;
    isLoading?: boolean;
}

export function CategoryModal({ categories, onSelect, onClose, isLoading }: Props) {
    const { services, selectedPlatform } = useApp();
    const [search, setSearch] = useState('');
    const [ready, setReady] = useState(false);

    // Delayed ready flag to ensure DOM is painted before showing list
    useEffect(() => {
        const t = setTimeout(() => setReady(true), 50);
        return () => clearTimeout(t);
    }, []);

    // Also re-trigger ready whenever services load
    useEffect(() => {
        if (services.length > 0) setReady(true);
    }, [services]);

    // Derive categories internally as a robust fallback
    const resolvedCategories = useMemo(() => {
        if (categories.length > 0) return categories;
        if (services.length === 0) return [];
        if (!selectedPlatform || selectedPlatform === 'top') return ['Top Services'];

        const allCategories = [...new Set(services.map(s => s.category))];
        const platformDef = PLATFORMS.find(p => p.id === selectedPlatform);
        if (!platformDef) return allCategories;

        if (selectedPlatform === 'other') {
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
    }, [categories, services, selectedPlatform]);

    const filtered = useMemo(() => {
        if (!search.trim()) return resolvedCategories;
        const q = search.toLowerCase();
        return resolvedCategories.filter(c => c.toLowerCase().includes(q));
    }, [resolvedCategories, search]);

    const showLoading = isLoading || (!ready && services.length === 0);
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

    // The scrollable list — use absolute max-height in px, no flex
    const listStyle: React.CSSProperties = {
        position: 'relative', // CRITICAL for older WebKit overflow scrolling
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

                {/* Search */}
                <div style={searchBoxStyle}>
                    <input
                        type="text"
                        placeholder="Search categories..."
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        style={inputStyle}
                    />
                </div>

                {/* List */}
                <div style={listStyle} data-count={filtered.length}>
                    {showLoading ? (
                        <div style={emptyStyle}>Loading categories...</div>
                    ) : filtered.length === 0 ? (
                        <div style={emptyStyle}>
                            {services.length === 0
                                ? 'Loading services...'
                                : `No categories found.\n(Platform: ${selectedPlatform})\n(Services: ${services.length})\n(Raw: ${categories.length})`}
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
