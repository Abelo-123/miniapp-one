import { useState, useMemo } from 'react';
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

    // Compute safe pixel heights instead of vh units for WebView compatibility
    const screenH = window.innerHeight || document.documentElement.clientHeight || 600;
    const sheetMaxH = Math.min(screenH * 0.75, 600);
    const listMaxH = Math.max(screenH * 0.45, 200);

    return (
        <div
            className="modal-overlay"
            onClick={onClose}
            style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 9999 }}
        >
            <div
                className="modal-sheet"
                onClick={e => e.stopPropagation()}
                style={{
                    minHeight: '300px',
                    maxHeight: `${sheetMaxH}px`,
                    display: 'flex',
                    flexDirection: 'column' as const,
                    overflow: 'hidden',
                }}
            >
                <div className="modal-header" style={{ flexShrink: 0 }}>
                    <span className="modal-title">Select Service</span>
                    <button className="modal-close" onClick={onClose}>✕</button>
                </div>
                
                <div className="modal-search" style={{ flexShrink: 0 }}>
                    <input
                        type="text"
                        className="modal-search-input"
                        placeholder="Search services..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                <div
                    className="modal-list"
                    onScroll={handleScroll}
                    style={{
                        overflowY: 'auto' as const,
                        WebkitOverflowScrolling: 'touch' as any,
                        minHeight: '150px',
                        maxHeight: `${listMaxH}px`,
                        display: 'block',
                        visibility: 'visible' as const,
                    }}
                >
                    {isLoading ? (
                        <div className="modal-empty" style={{ display: 'block', padding: '40px 20px', textAlign: 'center' }}>
                            Loading services...
                        </div>
                    ) : filtered.length === 0 ? (
                        <div className="modal-empty" style={{ display: 'block', padding: '40px 20px', textAlign: 'center' }}>
                            No services found
                        </div>
                    ) : (
                        visibleServices.map(svc => (
                            <div
                                key={svc.id}
                                className="modal-item"
                                onClick={() => onSelect(svc)}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    padding: '14px 16px',
                                    cursor: 'pointer',
                                    visibility: 'visible',
                                }}
                            >
                                <div className="modal-item-main" style={{ flex: 1, minWidth: 0 }}>
                                    <div className="modal-item-name">{svc.name}</div>
                                    <div className="modal-item-desc">
                                        #{svc.id} • {svc.min} – {svc.max.toLocaleString()}
                                        {svc.averageTime && ` • ⏱ ${svc.averageTime}`}
                                    </div>
                                </div>
                                <div className="modal-item-price">{formatETB(svc.rate)}</div>
                            </div>
                        ))
                    )}
                    {hasMore && !isLoading && (
                        <div
                            className="modal-load-more"
                            onClick={() => setVisibleCount(prev => Math.min(prev + BATCH_SIZE, filtered.length))}
                        >
                            Load more ({filtered.length - visibleCount} remaining)
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
