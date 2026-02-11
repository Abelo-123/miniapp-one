import { useState, useMemo } from 'react';
import { List, Section, Cell, Input, Modal, Placeholder } from '@telegram-apps/telegram-ui';
import type { Service } from '../../types';
import { formatETB } from '../../constants';

interface Props {
    services: Service[];
    onSelect: (service: Service) => void;
    onClose: () => void;
}

export function ServiceModal({ services, onSelect, onClose }: Props) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        if (!search.trim()) return services;
        const q = search.toLowerCase();
        return services.filter(s =>
            s.name.toLowerCase().includes(q) || s.id.toString().includes(q)
        );
    }, [services, search]);

    return (
        <Modal
            open
            onOpenChange={(open) => { if (!open) onClose(); }}
            header={<Modal.Header>Select Service</Modal.Header>}
            snapPoints={[0.85]}
        >
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
                        {filtered.map(svc => (
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
                    </Section>
                )}
            </List>
        </Modal>
    );
}
