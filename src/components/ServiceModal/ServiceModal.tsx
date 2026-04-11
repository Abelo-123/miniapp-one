import React, { useState, useMemo, useDeferredValue, useEffect } from 'react';
import { List, Section, Cell, Input, Modal, Placeholder } from '@telegram-apps/telegram-ui';
import { onBackButtonClick, showBackButton, hideBackButton } from '@telegram-apps/sdk-react';
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

// 1. Memoized Row Component to prevent re-renders while typing
const ServiceRow = React.memo(({ 
    svc, onSelect 
}: { 
    svc: Service, onSelect: (s: Service) => void 
}) => {
    return (
        <div className="svc-item" onClick={() => onSelect(svc)}>
            <div className="svc-id-pill">#{svc.id}</div>
            <div className="svc-name">{svc.name}</div>
            <div className="svc-footer">
                <span className="svc-price">{formatETB(svc.rate)} / 1000</span>
                <span className="svc-limits"> | Min: {svc.min} | Max: {svc.max.toLocaleString()}</span>
            </div>
        </div>
    );
});

export function ServiceModal({ category, recommendedIds, onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');
    const deferredSearch = useDeferredValue(search);
    const [visibleCount, setVisibleCount] = useState(BATCH_SIZE);
    
    const { data: categoryServices = [], isLoading: loading, isError } = useCategoryServices(category, recommendedIds);

    // 2. Native Back Button Flow
    useEffect(() => {
        try {
            showBackButton();
            const off = onBackButtonClick(() => {
                onClose();
            });
            return () => {
                off();
                hideBackButton();
            };
        } catch (e) {
            console.error('Back button setup failed', e);
        }
    }, [onClose]);

    const filtered = useMemo(() => {
        if (!deferredSearch.trim()) return categoryServices;
        const q = deferredSearch.toLowerCase();
        return categoryServices.filter(s =>
            s.name.toLowerCase().includes(q) || s.id.toString().includes(q)
        );
    }, [categoryServices, deferredSearch]);

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
            snapPoints={[0.9]}
        >
            <div style={{ display: 'flex', flexDirection: 'column', flex: 1, overflowY: 'auto', paddingTop: 0 }} onScroll={handleScroll}>
                <div style={{ padding: '8px 16px 12px' }}>
                    <Input
                        inputMode="search"
                        autoComplete="off"
                        spellCheck={false}
                        placeholder="Search services..."
                        value={search}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                        after={
                            search.length > 0 ? (
                                <div 
                                    onClick={() => setSearch('')} 
                                    style={{ padding: '0 8px', color: 'var(--tg-theme-hint-color)', cursor: 'pointer' }}
                                >
                                    ✕
                                </div>
                            ) : null
                        }
                    />
                </div>
                <List>
                    {loading ? (
                        <Section>
                            {[1, 2, 3, 4, 5].map(i => (
                                <div key={i} className="skeleton-row">
                                    <div className="skeleton-bar" style={{ width: '70%' }}></div>
                                    <div className="skeleton-bar" style={{ width: '40%', opacity: 0.6 }}></div>
                                </div>
                            ))}
                        </Section>
                    ) : isError ? (
                        <Placeholder description="Failed to load services" />
                    ) : filtered.length === 0 ? (
                        <Placeholder description="No services found" />
                    ) : (
                        <Section>
                            {visibleServices.map(svc => (
                                <ServiceRow 
                                    key={svc.id} 
                                    svc={svc} 
                                    onSelect={onSelect} 
                                />
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