import { useState, useMemo, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import { hapticImpact, hapticSelection } from '../../helpers/telegram';
import { Section, Cell, Button, Input } from '@telegram-apps/telegram-ui';
import type { OrderStatus } from '../../types';

const STATUS_FILTERS: { id: OrderStatus | 'all'; label: string }[] = [
    { id: 'all', label: 'All' },
    { id: 'pending', label: 'Pending' },
    { id: 'processing', label: 'Processing' },
    { id: 'completed', label: 'Compl' },
];

function normalizeStatus(status: string): OrderStatus {
    const s = status.toLowerCase();
    if (s === 'in_progress') return 'processing';
    if (s === 'canceled') return 'cancelled';
    return s as OrderStatus;
}

export function HistoryPage() {
    const { orders, showToast, refreshOrders } = useApp();
    const [filter, setFilter] = useState<OrderStatus | 'all'>('all');
    const [search, setSearch] = useState('');
    const [showSearch, setShowSearch] = useState(false);

    const filtered = useMemo(() => {
        let result = orders;

        if (filter !== 'all') {
            result = result.filter(o => normalizeStatus(o.status) === filter);
        }

        if (search.trim()) {
            const q = search.toLowerCase();
            result = result.filter(o =>
                (o.service_name || '').toLowerCase().includes(q) ||
                (o.api_order_id || '').toString().includes(q) ||
                (o.link || '').toLowerCase().includes(q)
            );
        }

        return result;
    }, [orders, filter, search]);

    const handleRefresh = useCallback(() => {
        hapticImpact('medium');
        refreshOrders();
        showToast('info', 'Refreshing orders...');
    }, [refreshOrders, showToast]);

    return (
        <div className="history-page">
            {/* ─── Filter Row with Search & Refresh ─── */}
            <div className="history-filter-row">
                <div className="filter-row">
                    {STATUS_FILTERS.map(f => (
                        <Button
                            key={f.id}
                            size="s"
                            mode={filter === f.id ? 'filled' : 'outline'}
                            onClick={() => {
                                hapticSelection();
                                setFilter(f.id);
                            }}
                            style={filter === f.id ? { background: 'var(--accent-primary)', color: '#fff', border: 'none' } : {}}
                        >
                            {f.label}
                        </Button>
                    ))}
                </div>
                <div className="history-actions">
                    <Button
                        mode="plain"
                        onClick={() => setShowSearch(!showSearch)}
                        style={{ padding: 8 }}
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    </Button>
                    <Button
                        mode="plain"
                        onClick={handleRefresh}
                        style={{ padding: 8 }}
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <polyline points="23 4 23 10 17 10" />
                            <polyline points="1 20 1 14 7 14" />
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                        </svg>
                    </Button>
                </div>
            </div>

            {/* ─── Inline Search ─── */}
            {showSearch && (
                <div style={{ margin: '0 16px 12px' }}>
                    <Input
                        type="text"
                        placeholder="Search by API order ID, service name, or link..."
                        value={search}
                        onChange={(e: any) => setSearch(e.target.value)}
                        autoFocus
                    />
                </div>
            )}

            {/* ─── Orders ─── */}
            <Section style={{ margin: '0 16px', background: 'var(--surface-glass)', border: '1px solid var(--surface-glass-border)' }}>
                {filtered.length === 0 ? (
                    <div className="empty-state">
                        <div className="empty-state__icon">📦</div>
                        <div className="empty-state__title">No Orders Found</div>
                        <div className="empty-state__text">
                            {search.trim() || filter !== 'all'
                                ? 'Try adjusting your filters'
                                : "You haven't placed any orders yet"}
                        </div>
                    </div>
                ) : (
                    filtered.map((order) => {
                        const status = normalizeStatus(order.status);
                        const serviceName = (order.service_name || '').length > 25
                            ? (order.service_name || '').substring(0, 25) + '...'
                            : (order.service_name || '');
                        const linkName = (order.link || '').length > 30
                            ? (order.link || '').substring(0, 30) + '...'
                            : (order.link || '');
                            
                        return (
                            <Cell
                                key={order.id}
                                before={<span style={{fontSize: 12, fontWeight: 700, opacity: 0.5}}>#{order.api_order_id}</span>}
                                subtitle={<span style={{color: 'var(--accent-secondary)'}}>{linkName}</span>}
                                after={
                                    <div style={{display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4}}>
                                        <span style={{fontWeight: 700}}>{order.quantity} qty</span>
                                        <span className={`history-table__status history-table__status--${status}`}>
                                            {status.toUpperCase()}
                                        </span>
                                    </div>
                                }
                            >
                                <span style={{fontWeight: 600, fontSize: 14}}>{serviceName}</span>
                            </Cell>
                        );
                    })
                )}
            </Section>
        </div>
    );
}
