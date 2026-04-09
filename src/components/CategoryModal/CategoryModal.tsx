import { useState, useMemo, useDeferredValue } from 'react';
import { List, Section, Cell, Input, Modal, Placeholder, Spinner } from '@telegram-apps/telegram-ui';
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
            <div style={{ display: 'flex', flexDirection: 'column', flex: 1, overflowY: 'auto' }}>
                <List>
                    <Section>
                        <Input
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
                            <Placeholder header={<Spinner size="l" />} description="Fetching categories..." />
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
