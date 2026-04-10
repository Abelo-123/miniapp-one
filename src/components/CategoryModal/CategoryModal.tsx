import React, { useState, useMemo, useDeferredValue, useEffect } from 'react';
import { List, Section, Cell, Input, Modal, Placeholder } from '@telegram-apps/telegram-ui';
import { onBackButtonClick, showBackButton, hideBackButton } from '@telegram-apps/sdk-react';
import type { SocialPlatform } from '../../types';
import { useCategories } from '../../hooks/useCategories';
import { PLATFORM_ICONS } from '../../components/PlatformGrid/PlatformGrid';

interface Props {
    platform: SocialPlatform;
    onSelect: (category: string) => void;
    onClose: () => void;
}

export function CategoryModal({ platform, onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');
    const deferredSearch = useDeferredValue(search);
    const { data: rawCategories = [], isLoading: loading } = useCategories(platform);

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

    const categories = useMemo(() => {
        if (platform === 'top') return ['Top Services'];
        return rawCategories;
    }, [rawCategories, platform]);

    const filtered = useMemo(() => {
        if (!deferredSearch.trim()) return categories;
        const q = deferredSearch.toLowerCase();
        return categories.filter(c => c.toLowerCase().includes(q));
    }, [categories, deferredSearch]);

    return (
        <Modal
            open
            onOpenChange={(open) => { if (!open) onClose(); }}
            header={<Modal.Header>Select Category</Modal.Header>}
            snapPoints={[0.9]} 
        >
            <div style={{ display: 'flex', flexDirection: 'column', flex: 1, overflowY: 'auto', paddingTop: 0 }}>
                <List>
                    <Section className="modal-search">
                        <Input
                            inputMode="search"
                            autoComplete="off"
                            spellCheck={false}
                            placeholder="Search categories..."
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
                    </Section>
                    {loading ? (
                        <Section>
                            {[1, 2, 3, 4, 5, 6, 7].map(i => (
                                <div key={i} className="skeleton-row">
                                    <div className="skeleton-bar" style={{ width: '60%' }}></div>
                                    <div className="skeleton-bar" style={{ width: '30%', opacity: 0.6 }}></div>
                                </div>
                            ))}
                        </Section>
                    ) : filtered.length === 0 ? (
                        <Placeholder description="No categories found" />
                    ) : (
                        <Section>
                            {filtered.map(cat => (
                                <Cell
                                    key={cat}
                                    before={
                                        <div style={{ width: 24, height: 24, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                            {PLATFORM_ICONS[platform] || '📂'}
                                        </div>
                                    }
                                    onClick={() => onSelect(cat)}
                                >
                                    {cat}
                                </Cell>
                            ))}
                        </Section>
                    )}
                </List>
            </div>
        </Modal>
    );
}
