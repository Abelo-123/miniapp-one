import { useState, useMemo, useCallback, useDeferredValue } from 'react';
import { useApp } from '../../context/AppContext';
import { hapticImpact, hapticSelection } from '../../helpers/telegram';
import { Section, Cell, Button, Input } from '@telegram-apps/telegram-ui';
import type { OrderStatus } from '../../types';

const STATUS_FILTERS: { id: OrderStatus | 'all'; label: string }[] = [
    { id: 'all', label: 'All' },
    { id: 'pending', label: 'Pending' },
    { id: 'processing', label: 'Processing' },
    { id: 'completed', label: 'Completed' },
];

function normalizeStatus(status: string): OrderStatus {
    const s = status.toLowerCase();
    if (s === 'in_progress') return 'processing';
    if (s === 'canceled') return 'cancelled';
    return s as OrderStatus;
}

export function HistoryPage() {
    const { orders, showToast, refreshOrders, setActiveTab } = useApp();
    const [filter, setFilter] = useState<OrderStatus | 'all'>('all');
    const [search, setSearch] = useState('');
    const deferredSearch = useDeferredValue(search);
    const [showSearch, setShowSearch] = useState(false);
    const [isRefreshing, setIsRefreshing] = useState(false);

    const filtered = useMemo(() => {
        let result = orders;

        if (filter !== 'all') {
            result = result.filter(o => normalizeStatus(o.status) === filter);
        }

        if (deferredSearch.trim()) {
            const q = deferredSearch.toLowerCase();
            result = result.filter(o =>
                (o.service_name || '').toLowerCase().includes(q) ||
                (o.api_order_id || '').toString().includes(q) ||
                (o.link || '').toLowerCase().includes(q)
            );
        }

        return result;
    }, [orders, filter, deferredSearch]);

    const handleRefresh = useCallback(() => {
        hapticImpact('medium');
        setIsRefreshing(true);
        refreshOrders();
        showToast('info', 'Refreshing orders...');
        
        // Remove the spin class after animation completes so it can be triggered again
        setTimeout(() => setIsRefreshing(false), 600);
    }, [refreshOrders, showToast]);

    return (
        <div className="history-page">
            <div className="history-filter-row" style={{
                position: 'sticky',
                top: 0,
                zIndex: 10,
                background: 'var(--tg-theme-bg-color)',
                backgroundColor: 'rgba(var(--tg-theme-bg-color), 0.8)', 
                backdropFilter: 'blur(16px)',
                WebkitBackdropFilter: 'blur(16px)',
                paddingTop: '16px',
                paddingBottom: '12px',
                borderBottom: '1px solid var(--tg-theme-secondary-bg-color)',
                margin: '0 -16px 16px -16px', // Pull out to screen edges
                paddingLeft: '16px',
                paddingRight: '16px'
            }}>
                <div 
                    className="filter-row"
                    style={{
                        display: 'flex',
                        gap: '8px', 
                        overflowX: 'auto', 
                        flex: 1, 
                        paddingRight: '8px', 
                        scrollbarWidth: 'none', 
                    }}
                >
                    {STATUS_FILTERS.map(f => (
                        <Button
                            key={f.id}
                            size="s"
                            mode={filter === f.id ? 'filled' : 'outline'}
                            onClick={() => {
                                hapticSelection();
                                setFilter(f.id);
                            }}
                            style={{
                                flexShrink: 0, // Prevent the button from shrinking
                                whiteSpace: 'nowrap', // Prevent text from wrapping to next line
                                ...(filter === f.id ? { background: 'var(--accent-primary)', color: '#fff', border: 'none' } : {})
                            }}
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
                        <svg 
                            className={isRefreshing ? 'spin-animation' : ''} 
                            width="18" height="18" viewBox="0 0 24 24" 
                            fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
                        >
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
            )}

            {/* ─── Orders ─── */}
            <Section style={{ margin: '0 16px', background: 'var(--tg-theme-secondary-bg-color)', borderRadius: '12px', overflow: 'hidden' }}>
                {filtered.length === 0 ? (
                    <div className="empty-state">
                        <div className="empty-state__icon">📦</div>
                        <div className="empty-state__title">No Orders Found</div>
                        <div className="empty-state__text">
                            {search.trim() || filter !== 'all'
                                ? 'Try adjusting your filters'
                                : "You haven't placed any orders yet"}
                        </div>
                        
                        {/* NEW: Actionable button to guide the user */}
                        {(!search.trim() && filter === 'all') && (
                            <Button 
                                size="m" 
                                style={{ marginTop: '16px', background: 'var(--accent-primary)', color: '#fff' }}
                                onClick={() => setActiveTab('order')}
                            >
                                Place an Order
                            </Button>
                        )}
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
                                // Make the ID look like a sleek pill instead of plain text
                                before={
                                    <div 
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            navigator.clipboard.writeText(order.api_order_id.toString());
                                            showToast('info', 'ID copied to clipboard');
                                            hapticImpact('light');
                                        }}
                                        style={{
                                            background: 'var(--surface-elevated)', 
                                            padding: '4px 8px', 
                                            borderRadius: '6px', 
                                            fontSize: '11px', 
                                            fontWeight: 700, 
                                            color: 'var(--tg-theme-hint-color)',
                                            cursor: 'pointer'
                                        }}
                                    >
                                        #{order.api_order_id}
                                    </div>
                                }
                                subtitle={
                                    <span style={{color: 'var(--accent-secondary)', fontSize: '12px'}}>
                                        {linkName}
                                    </span>
                                }
                                after={
                                    <div style={{display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 6}}>
                                        <span style={{fontWeight: 700, fontSize: '14px'}}>{order.quantity} <span style={{fontSize:'10px', opacity:0.6}}>QTY</span></span>
                                        {/* Using inline styles to guarantee the badge looks native */}
                                        <span style={{
                                            fontSize: '10px', 
                                            fontWeight: 800, 
                                            padding: '3px 8px', 
                                            borderRadius: '12px',
                                            textTransform: 'uppercase',
                                            background: status === 'completed' ? 'rgba(0,214,143,0.15)' : 
                                                        status === 'processing' ? 'rgba(91,141,239,0.15)' : 
                                                        status === 'cancelled' ? 'rgba(255,71,87,0.15)' : 'rgba(255,165,2,0.15)',
                                            color: status === 'completed' ? 'var(--color-success)' : 
                                                   status === 'processing' ? 'var(--color-info)' : 
                                                   status === 'cancelled' ? 'var(--color-danger)' : 'var(--color-warning)'
                                        }}>
                                            {status}
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
