import { useEffect } from 'react';
import { useApp } from '../../context/AppContext';
import { Section, Cell } from '@telegram-apps/telegram-ui';

interface Props {
    onBack: () => void;
}

export function NotificationPanel({ onBack }: Props) {
    const { alerts, unreadAlerts, setUnreadAlerts, setAlerts } = useApp();

    const markAlertsRead = () => {
        if (unreadAlerts > 0) {
            setUnreadAlerts(0);
            setAlerts(alerts.map(a => ({ ...a, is_read: true })));
            import('../../api').then(api => api.markAlertsRead().catch(() => {}));
        }
    };

    // Auto-mark as read when user opens the panel
    useEffect(() => {
        if (unreadAlerts > 0) {
            markAlertsRead();
        }
    }, [unreadAlerts]);

    return (
        <div style={{
            position: 'fixed',
            top: 0, left: 0, right: 0, bottom: 0,
            backgroundColor: 'var(--tg-theme-bg-color, #ffffff)',
            zIndex: 9999,
            display: 'flex',
            flexDirection: 'column',
            animation: 'slideUp 0.3s ease-out'
        }}>
            <div style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '16px',
                borderBottom: '1px solid var(--tg-theme-hint-color, #e0e0e0)'
            }}>
                <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 600 }}>Notifications</h2>
                <button 
                    onClick={onBack}
                    style={{
                        background: 'transparent',
                        border: 'none',
                        color: 'var(--tg-theme-text-color, #000)',
                        cursor: 'pointer',
                        padding: '4px'
                    }}
                >
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div style={{ flex: 1, overflowY: 'auto', padding: '16px' }}>
                <Section>
                    {alerts.length === 0 ? (
                        <div style={{ textAlign: 'center', padding: '40px', color: 'var(--tg-theme-hint-color, #999)' }}>
                            No notifications
                        </div>
                    ) : (
                        alerts.map((alert) => (
                            <Cell
                                key={alert.id}
                                onClick={markAlertsRead}
                                before={!alert.is_read && <div style={{ width: 8, height: 8, background: '#ff3b30', borderRadius: '50%', marginRight: 12 }} />}
                                subtitle={new Date(alert.created_at).toLocaleDateString()}
                            >
                                <span style={{ fontSize: 13, lineHeight: 1.4, whiteSpace: 'normal', color: 'var(--tg-theme-text-color)' }}>
                                    {alert.message}
                                </span>
                            </Cell>
                        ))
                    )}
                </Section>
            </div>
        </div>
    );
}
