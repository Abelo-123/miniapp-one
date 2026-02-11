import { useState, useMemo } from 'react';
import { List, Section, Cell, Input, Modal, Placeholder } from '@telegram-apps/telegram-ui';

interface Props {
    categories: string[];
    onSelect: (category: string) => void;
    onClose: () => void;
}

export function CategoryModal({ categories, onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        if (!search.trim()) return categories;
        const q = search.toLowerCase();
        return categories.filter(c => c.toLowerCase().includes(q));
    }, [categories, search]);

    return (
        <Modal
            open
            onOpenChange={(open) => { if (!open) onClose(); }}
            header={<Modal.Header>Select Category</Modal.Header>}
        >
            <List>
                <Section>
                    <Input
                        placeholder="Search categories..."
                        value={search}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                    />
                </Section>
                {filtered.length === 0 ? (
                    <Placeholder description="No categories found" />
                ) : (
                    <Section>
                        {filtered.map(cat => (
                            <Cell
                                key={cat}
                                before={<span style={{ fontSize: 18 }}>📂</span>}
                                onClick={() => onSelect(cat)}
                            >
                                {cat}
                            </Cell>
                        ))}
                    </Section>
                )}
            </List>
        </Modal>
    );
}
