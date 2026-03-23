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

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div
                className="modal-sheet"
                onClick={e => e.stopPropagation()}
                style={{ minHeight: '40vh' }}
            >
                <div className="modal-header">
                    <span className="modal-title">Select Service</span>
                    <button className="modal-close" onClick={onClose}>✕</button>
                </div>
                
                <div className="modal-search">
                    <input
                        type="text"
                        className="modal-search-input"
                        placeholder="Search services..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                <div className="modal-list" onScroll={handleScroll}>
                    {isLoading ? (
                        <div className="modal-empty">Loading services...</div>
                    ) : filtered.length === 0 ? (
                        <div className="modal-empty">No services found</div>
                    ) : (
                        visibleServices.map(svc => (
                            <div
                                key={svc.id}
                                className="modal-item"
                                onClick={() => onSelect(svc)}
                            >
                                <div className="modal-item-main">
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
