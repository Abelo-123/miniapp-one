import { useState, useMemo, useCallback, useEffect, useRef } from 'react';
import { List, Section, Cell, Input, Modal, Placeholder } from '@telegram-apps/telegram-ui';
import type { Service } from '../../types';
import { formatETB } from '../../constants';

interface Props {
    services: Service[];
    onSelect: (service: Service) => void;
    onClose: () => void;
}

const BATCH_SIZE = 50;

export function ServiceModal({ services, onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');
    const [visibleCount, setVisibleCount] = useState(BATCH_SIZE);
    const listRef = useRef<HTMLDivElement>(null);

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

    const handleScroll = useCallback(() => {
        if (!listRef.current) return;
        const { scrollTop, scrollHeight, clientHeight } = listRef.current;
        if (scrollHeight - scrollTop - clientHeight < 200 && hasMore) {
            setVisibleCount(prev => Math.min(prev + BATCH_SIZE, filtered.length));
        }
    }, [hasMore, filtered.length]);

    useEffect(() => {
        const el = listRef.current;
        if (!el) return;
        el.addEventListener('scroll', handleScroll);
        return () => el.removeEventListener('scroll', handleScroll);
    }, [handleScroll]);

    useEffect(() => {
        setVisibleCount(BATCH_SIZE);
    }, [search]);

    return (
        <Modal
            open
            onOpenChange={(open) => { if (!open) onClose(); }}
            header={<Modal.Header>Select Service</Modal.Header>}
            snapPoints={[0.85]}
        >
            <div ref={listRef} style={{ maxHeight: '60vh', overflow: 'auto' }}>
            <List>
                <Section>
                    <Input
                        placeholder="Search services..."
                        value={search}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                    />
                </Section>
                {filtered.length === 0 ? (
                    <Placeholder description="No services found" />
                ) : (
                    <Section>
                        {visibleServices.map(svc => (
                            <Cell
                                key={svc.id}
                                multiline
                                onClick={() => onSelect(svc)}
                                description={
                                    <span style={{ fontSize: 12 }}>
                                        #{svc.id} • {svc.min} – {svc.max.toLocaleString()}
                                        {svc.averageTime && ` • ⏱ ${svc.averageTime}`}
                                        {svc.refill && ' • ♻ Refill'}
                                        {svc.cancel && ' • ✕ Cancel'}
                                    </span>
                                }
                                after={
                                    <span style={{
                                        fontWeight: 600,
                                        color: 'var(--tg-theme-link-color)',
                                    }}>
                                        {formatETB(svc.rate)}
                                    </span>
                                }
                            >
                                {svc.name}
                            </Cell>
                        ))}
                        {hasMore && (
                            <Cell
                                onClick={() => setVisibleCount(prev => Math.min(prev + BATCH_SIZE, filtered.length))}
                                style={{ textAlign: 'center', color: 'var(--tg-theme-link-color)' }}
                            >
                                Load more ({filtered.length - visibleCount} remaining)
                            </Cell>
                        )}
                    </Section>
                )}
            </List>
            </div>
        </Modal>
    );
}
