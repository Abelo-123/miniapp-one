import { useState, useMemo } from 'react';
import { List, Section, Cell, Input, Placeholder } from '@telegram-apps/telegram-ui';
import type { Service } from '../../types';
import { formatETB } from '../../constants';
import { useAllServices } from '../../hooks/useAllServices';

interface Props {
    onSelect: (service: Service) => void;
    onClose: () => void;
}

export function SearchModal({ onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');
    const { data: services = [], isLoading } = useAllServices();

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
        <div style={{
            position: 'fixed',
            top: 0, left: 0, right: 0, bottom: 0,
            backgroundColor: 'var(--tg-theme-bg-color, #ffffff)',
            zIndex: 9999,
            display: 'flex',
            flexDirection: 'column',
            animation: 'slideUp 0.3s ease-out'
        }}>
            <div style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '16px',
                borderBottom: '1px solid var(--tg-theme-hint-color, #e0e0e0)'
            }}>
                <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 600 }}>🔍 Search</h2>
                <button 
                    onClick={onClose}
                    style={{
                        background: 'transparent',
                        border: 'none',
                        color: 'var(--tg-theme-text-color, #000)',
                        cursor: 'pointer',
                        padding: '4px'
                    }}
                >
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div style={{ flex: 1, overflowY: 'auto', padding: '16px', paddingTop: '0px' }}>
                <List>
                    <Section>
                        <Input
                            autoFocus
                            placeholder="Type name, ID, or category..."
                            value={search}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                        />
                    </Section>

                    {isLoading ? (
                        <Placeholder description="Loading services..." />
                    ) : search.trim() === '' ? (
                        <Placeholder description="Start typing to search" />
                    ) : results.length === 0 ? (
                        <Placeholder description="No services match your search" />
                    ) : (
                        Array.from(grouped.entries()).map(([category, svcs]) => (
                            <Section key={category} header={category}>
                                {svcs.map(svc => (
                                    <Cell
                                        key={svc.id}
                                        multiline
                                        onClick={() => onSelect(svc)}
                                        description={
                                            <span style={{ fontSize: 12, color: 'var(--tg-theme-hint-color, #999)' }}>
                                                {svc.category}
                                            </span>
                                        }
                                        after={
                                            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '4px' }}>
                                                <span style={{ 
                                                    fontSize: 11, 
                                                    fontWeight: 600, 
                                                    color: 'var(--accent, #7c5cfc)',
                                                    background: 'rgba(124, 92, 252, 0.1)',
                                                    padding: '2px 6px',
                                                    borderRadius: '8px'
                                                }}>
                                                    ID: {svc.id}
                                                </span>
                                                <span style={{ fontSize: 13, fontWeight: 600, color: 'var(--tg-theme-text-color)' }}>
                                                    {formatETB(svc.rate)}
                                                </span>
                                            </div>
                                        }
                                    >
                                        {svc.name}
                                    </Cell>
                                ))}
                            </Section>
                        ))
                    )}
                </List>
            </div>
        </div>
    );
}
