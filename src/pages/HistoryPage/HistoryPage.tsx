import { useState, useMemo } from 'react';
import { List, Section, Cell, Input, Button, Placeholder } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';
import { formatETB } from '../../constants';
import { hapticImpact, hapticSelection } from '../../helpers/telegram';
import type { OrderStatus } from '../../types';
// Styles handled by TUI components

const STATUS_FILTERS: { id: OrderStatus | 'all'; label: string }[] = [
    { id: 'all', label: 'All' },
    { id: 'pending', label: 'Pending' },
    { id: 'processing', label: 'Processing' },
    { id: 'completed', label: 'Completed' },
    { id: 'cancelled', label: 'Cancelled' },
];

function normalizeStatus(status: string): OrderStatus {
    const s = status.toLowerCase();
    if (s === 'in_progress') return 'processing';
    if (s === 'canceled') return 'cancelled';
    return s as OrderStatus;
}

function formatDate(dateStr: string): string {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export function HistoryPage() {
    const { orders, showToast } = useApp();
    const [filter, setFilter] = useState<OrderStatus | 'all'>('all');
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        let result = orders;

        // Status filter
        if (filter !== 'all') {
            result = result.filter(o => normalizeStatus(o.status) === filter);
        }

        // Search filter
        if (search.trim()) {
            const q = search.toLowerCase();
            result = result.filter(o =>
                o.service_name.toLowerCase().includes(q) ||
                o.api_order_id.toString().includes(q) ||
                o.link.toLowerCase().includes(q)
            );
        }

        return result;
    }, [orders, filter, search]);

    const handleRefill = (orderId: number) => {
        hapticImpact('medium');
        showToast('info', `Refill request sent for order #${orderId}`);
    };

    return (
        <List>
            <Section>
                <Input
                    placeholder="Search orders..."
                    value={search}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                />

                {/* Scrollable Filters */}
                <div style={{
                    padding: '12px 16px',
                    overflowX: 'auto',
                    display: 'flex',
                    gap: 8,
                    scrollbarWidth: 'none',
                    msOverflowStyle: 'none',
                    background: 'var(--tg-theme-bg-color)'
                }}>
                    {STATUS_FILTERS.map(f => (
                        <Button
                            key={f.id}
                            size="s"
                            mode={filter === f.id ? 'filled' : 'bezeled'}
                            onClick={() => {
                                hapticSelection();
                                setFilter(f.id);
                            }}
                        >
                            {f.label}
                        </Button>
                    ))}
                </div>
            </Section>

            {filtered.length === 0 ? (
                <Placeholder
                    header="No Orders Found"
                    description={search.trim() || filter !== 'all' ? "Try adjusting your filters" : "You haven't placed any orders yet."}
                >
                    {/* Optional Icon/Image */}
                </Placeholder>
            ) : (
                <Section header={`Orders (${filtered.length})`}>
                    {filtered.map(order => {
                        const status = normalizeStatus(order.status);
                        const canRefill = status === 'completed';

                        return (
                            <Cell
                                key={order.id}
                                multiline
                                description={
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: 4, marginTop: 4 }}>
                                        <div style={{ fontSize: '13px', color: 'var(--tg-theme-link-color)' }}>{order.link}</div>
                                        <div style={{ display: 'flex', gap: 12, fontSize: '12px', color: 'var(--tg-theme-hint-color)' }}>
                                            <span>Qty: {order.quantity}</span>
                                            <span>Start: {order.start_count}</span>
                                            <span>Remains: {order.remains}</span>
                                        </div>
                                        <div style={{ fontSize: '12px', color: 'var(--tg-theme-hint-color)' }}>
                                            {formatDate(order.created_at)}
                                        </div>
                                    </div>
                                }
                                after={
                                    <div style={{ textAlign: 'right', display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4 }}>
                                        <div style={{ fontWeight: 600 }}>{formatETB(order.charge)}</div>
                                        <div style={{
                                            fontSize: '11px',
                                            padding: '2px 6px',
                                            borderRadius: '4px',
                                            backgroundColor: 'var(--tg-theme-secondary-bg-color)',
                                            color: 'var(--tg-theme-text-color)'
                                        }}>
                                            {status.toUpperCase()}
                                        </div>
                                        {canRefill && (
                                            <Button size="s" mode="plain" onClick={(e) => {
                                                e.stopPropagation();
                                                handleRefill(order.api_order_id);
                                            }}>
                                                Refill
                                            </Button>
                                        )}
                                    </div>
                                }
                            >
                                <span style={{ fontWeight: 600 }}>#{order.api_order_id} {order.service_name}</span>
                            </Cell>
                        );
                    })}
                </Section>
            )}
        </List>
    );
}
