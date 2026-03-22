import { useState, useMemo } from 'react';
import type { Service } from '../../types';
import { formatETB } from '../../constants';

interface Props {
    services: Service[];
    onSelect: (service: Service) => void;
    onClose: () => void;
}

export function SearchModal({ services, onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');

    const results = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return [];
        const terms = q.split(/\s+/);
        return services.filter(s => {
            const haystack = `${s.name} ${s.category} ${s.id}`.toLowerCase();
            return terms.every(t => haystack.includes(t));
        }).slice(0, 30);
    }, [services, search]);

    const grouped = useMemo(() => {
        const map = new Map<string, Service[]>();
        for (const s of results) {
            const arr = map.get(s.category) || [];
            arr.push(s);
            map.set(s.category, arr);
        }
        return map;
    }, [results]);

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal-sheet modal-sheet--large" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <span className="modal-title">🔍 Search All Services</span>
                    <button className="modal-close" onClick={onClose}>✕</button>
                </div>
                
                <div className="modal-search">
                    <input
                        type="text"
                        className="modal-search-input"
                        placeholder="Search by name, ID, or category..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        autoFocus
                    />
                </div>

                <div className="modal-list">
                    {search.trim() === '' ? (
                        <div className="modal-empty">Start typing to search across all services</div>
                    ) : results.length === 0 ? (
                        <div className="modal-empty">No services match your search</div>
                    ) : (
                        Array.from(grouped.entries()).map(([category, svcs]) => (
                            <div key={category}>
                                <div className="modal-group-header">{category}</div>
                                {svcs.map(svc => (
                                    <div
                                        key={svc.id}
                                        className="modal-item"
                                        onClick={() => onSelect(svc)}
                                    >
                                        <div className="modal-item-main">
                                            <div className="modal-item-name">{svc.name}</div>
                                            <div className="modal-item-desc">#{svc.id} • {formatETB(svc.rate)}/1K</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
