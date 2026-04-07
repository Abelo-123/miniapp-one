import { useState, useMemo } from 'react';
import { List, Section, Cell, Input, Modal, Placeholder } from '@telegram-apps/telegram-ui';
import type { Service } from '../../types';
import { formatETB } from '../../constants';
import { useCategoryServices } from '../../hooks/useCategoryServices';

interface Props {
    category: string;
    recommendedIds: number[];
    onSelect: (service: Service) => void;
    onClose: () => void;
}

const BATCH_SIZE = 50;

export function ServiceModal({ category, recommendedIds, onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');
    const [visibleCount, setVisibleCount] = useState(BATCH_SIZE);
    
    const { data: categoryServices = [], isLoading: loading, isError } = useCategoryServices(category, recommendedIds);

    const filtered = useMemo(() => {
        if (!search.trim()) return categoryServices;
        const q = search.toLowerCase();
        return categoryServices.filter(s =>
            s.name.toLowerCase().includes(q) || s.id.toString().includes(q)
        );
    }, [categoryServices, search]);

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
        <Modal
            open
            onOpenChange={(open) => { if (!open) onClose(); }}
            header={<Modal.Header>Select Service</Modal.Header>}
            snapPoints={[0.85]}
        >
            <div style={{ maxHeight: '60vh', overflow: 'auto' }} onScroll={handleScroll}>
                <List>
                    <Section>
                        <Input
                            placeholder="Search services..."
                            value={search}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                        />
                    </Section>
                    {loading ? (
                        <Placeholder description="Loading services..." />
                    ) : isError ? (
                        <Placeholder description="Failed to load services" />
                    ) : filtered.length === 0 ? (
                        <Placeholder description="No services found" />
                    ) : (
                        <Section>
                            {visibleServices.map(svc => (
                                <Cell
                                    key={svc.id}
                                    multiline
                                    onClick={() => onSelect(svc)}
                                    description={
                                        <span style={{ fontSize: 12, color: 'var(--tg-theme-hint-color, #999)' }}>
                                            Min: {svc.min} – Max: {svc.max.toLocaleString()}
                                            {svc.averageTime && ` • ⏱ ${svc.averageTime}`}
                                            {svc.refill && ' • ♻ Refill'}
                                            {svc.cancel && ' • ✕ Cancel'}
                                        </span>
                                    }
                                    after={
                                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '4px', minWidth: 'max-content' }}>
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
