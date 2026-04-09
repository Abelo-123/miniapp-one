import { useState, useMemo, useEffect } from 'react';
import type { Service } from '../../types';
import { formatETB } from '../../constants';
import { getServices } from '../../api';
import { Input } from '@telegram-apps/telegram-ui';

interface Props {
    onSelect: (service: Service) => void;
    onClose: () => void;
}

export function SearchPanel({ onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');
    const [rawServices, setRawServices] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

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

    const services = useMemo<Service[]>(() => {
        if (rawServices.length === 0) return [];
        return rawServices.map((s: any) => ({
            id: s.service,
            category: s.category,
            name: s.name,
            type: s.type as Service['type'],
            rate: parseFloat(s.rate),
            min: s.min,
            max: s.max,
            averageTime: s.average_time || s.averageTime || '',
            refill: s.refill,
            cancel: s.cancel,
        }));
    }, [rawServices]);

    const results = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return [];
        const terms = q.split(/\s+/);
        return services.filter(s => {
            const haystack = `${s.name} ${s.category} ${s.id}`.toLowerCase();
            return terms.every(t => haystack.includes(t));
        }).slice(0, 30);
    }, [services, search]);

    return (
        <div className="search-page">
            <div className="search-page__header">
                <button className="search-page__back" onClick={onClose}>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                </button>
                <div className="search-page__input-wrapper" style={{ flex: 1 }}>
                    <Input
                        inputMode="search"
                        autoComplete="off"
                        spellCheck={false}
                        type="text"
                        placeholder="Search services..."
                        value={search}
                        onChange={(e: any) => setSearch(e.target.value)}
                        autoFocus
                        style={{ border: '2px solid rgba(124,92,252,0.5)', borderRadius: 8, background: 'transparent' }}
                    />
                </div>
            </div>

            <div className="search-page__results">
                {loading ? (
                    <div className="search-page__loading">
                        <div className="loading-overlay__spinner" />
                        <span>Loading services...</span>
                    </div>
                ) : search.trim() === '' ? (
                    <div className="search-page__empty">
                        Start typing to search across all services
                    </div>
                ) : results.length === 0 ? (
                    <div className="search-page__empty">
                        No services match your search
                    </div>
                ) : (
                    results.map(svc => (
                        <div
                            key={svc.id}
                            className="search-result-card"
                            onClick={() => onSelect(svc)}
                        >
                            <div className="search-result-card__header">
                                <span className="search-result-card__id">#{svc.id}</span>
                                <span className="search-result-card__badge">NEW THINGS</span>
                            </div>
                            <div className="search-result-card__name">{svc.name}</div>
                            <div className="search-result-card__meta">
                                <div className="search-result-card__meta-left">
                                    <span>Min: {svc.min}</span>
                                    <span>Max: {svc.max.toLocaleString()}</span>
                                </div>
                                <div className="search-result-card__meta-right">
                                    <span className="search-result-card__speed">FAST START</span>
                                    <span className="search-result-card__price">{formatETB(svc.rate)} / 1K</span>
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}
