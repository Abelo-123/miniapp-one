import React, { useState, useMemo, useEffect } from 'react';
import type { Service } from '../../types';
import { formatETB } from '../../constants';

interface Props {
    services: Service[];
    onSelect: (service: Service) => void;
    onClose: () => void;
    isLoading?: boolean;
}

const BATCH_SIZE = 50;

export function ServiceModal({ services, onSelect, onClose, isLoading }: Props) {
    const [search, setSearch] = useState('');
    const [visibleCount, setVisibleCount] = useState(BATCH_SIZE);
    const [ready, setReady] = useState(false);

    // Delayed ready flag to ensure DOM is painted before showing list
    useEffect(() => {
        const t = setTimeout(() => setReady(true), 50);
        return () => clearTimeout(t);
    }, []);

    const filtered = useMemo(() => {
        if (!search.trim()) return services;
        const q = search.toLowerCase();
        return services.filter(s =>
            s.name.toLowerCase().includes(q) || s.id.toString().includes(q)
        );
    }, [services, search]);

    const visibleServices = useMemo(() => {
        return filtered.slice(0, visibleCount);
    }, [filtered, visibleCount]);

    const hasMore = visibleCount < filtered.length;

    const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
        const target = e.currentTarget;
        if (target.scrollHeight - target.scrollTop - target.clientHeight < 200 && hasMore) {
            setVisibleCount(prev => Math.min(prev + BATCH_SIZE, filtered.length));
        }
    };

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

    // The scrollable list — use absolute max-height in px, no flex, overflowY: scroll
    const listStyle: React.CSSProperties = {
        position: 'relative',
        overflowY: 'auto',
        maxHeight: Math.max(screenH * 0.55, 250),
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
                        Select Service
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
                        placeholder="Search services..."
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        style={inputStyle}
                    />
                </div>

                {/* List */}
                <div style={listStyle} onScroll={handleScroll} data-count={filtered.length}>
                    {isLoading || (!ready && services.length === 0) ? (
                        <div style={emptyStyle}>Loading services...</div>
                    ) : filtered.length === 0 ? (
                        <div style={emptyStyle}>
                            {`No services found.\n(Total: ${services.length})`}
                        </div>
                    ) : (
                        <>
                            {visibleServices.map(svc => (
                                <div key={svc.id} style={itemStyle} onClick={() => onSelect(svc)}>
                                    <div style={{ flex: 1, minWidth: 0, paddingRight: 12 }}>
                                        <div style={{ fontSize: 14, fontWeight: 500, color: 'var(--tg-theme-text-color, #fff)', marginBottom: 4, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                            {svc.name}
                                        </div>
                                        <div style={{ fontSize: 12, color: 'rgba(255,255,255,0.5)' }}>
                                            #{svc.id} • {svc.min} – {svc.max.toLocaleString()}
                                            {svc.averageTime && ` • ⏱ ${svc.averageTime}`}
                                        </div>
                                    </div>
                                    <div style={{ fontSize: 14, fontWeight: 600, color: 'var(--tg-theme-link-color, #6ab3f3)' }}>
                                        {formatETB(svc.rate)}
                                    </div>
                                </div>
                            ))}
                            {hasMore && !isLoading && (
                                <div
                                    onClick={() => setVisibleCount(prev => Math.min(prev + BATCH_SIZE, filtered.length))}
                                    style={{
                                        padding: '14px 16px',
                                        textAlign: 'center',
                                        color: 'var(--tg-theme-link-color, #6ab3f3)',
                                        fontSize: 14,
                                        cursor: 'pointer'
                                    }}
                                >
                                    Load more ({filtered.length - visibleCount} remaining)
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
