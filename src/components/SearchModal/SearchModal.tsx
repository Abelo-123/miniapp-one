import { useState, useMemo } from 'react';
import { List, Section, Cell, Input, Modal, Placeholder } from '@telegram-apps/telegram-ui';
import type { Service } from '../../types';
import { formatETB } from '../../constants';

interface Props {
    services: Service[];
    onSelect: (service: Service) => void;
    onClose: () => void;
}

function highlightMatch(text: string, query: string): JSX.Element {
    if (!query) return <>{text}</>;
    const idx = text.toLowerCase().indexOf(query.toLowerCase());
    if (idx === -1) return <>{text}</>;
    return (
        <>
            {text.slice(0, idx)}
            <mark style={{
                background: 'var(--tg-theme-accent-text-color, rgba(108,92,231,0.3))',
                color: 'var(--tg-theme-text-color)',
                borderRadius: 2,
                padding: '0 2px',
                opacity: 0.7,
            }}>
                {text.slice(idx, idx + query.length)}
            </mark>
            {text.slice(idx + query.length)}
        </>
    );
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

    // Group results by category
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
        <Modal
            open
            onOpenChange={(open) => { if (!open) onClose(); }}
            header={<Modal.Header>🔍 Search All Services</Modal.Header>}
            snapPoints={[0.9]}
        >
            <List>
                <Section>
                    <Input
                        placeholder="Search by name, ID, or category..."
                        value={search}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                    />
                </Section>

                {search.trim() === '' ? (
                    <Placeholder description="Start typing to search across all services" />
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
                                        <span style={{ fontSize: 12 }}>
                                            #{svc.id} • {formatETB(svc.rate)}/1K
                                        </span>
                                    }
                                >
                                    {highlightMatch(svc.name, search.trim())}
                                </Cell>
                            ))}
                        </Section>
                    ))
                )}
            </List>
        </Modal>
    );
}
