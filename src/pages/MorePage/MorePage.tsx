import { useState, useRef, useEffect } from 'react';
import { List, Section, Cell, Avatar, Input, Button, Placeholder } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';

export function MorePage() {
    const {
        user, alerts, chatMessages, setChatMessages,
        unreadAlerts, setUnreadAlerts, setAlerts, showToast,
    } = useApp();

    const [chatInput, setChatInput] = useState('');
    const chatEndRef = useRef<HTMLDivElement>(null);

    // Scroll chat to bottom
    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [chatMessages]);

    // Mark alerts as read
    const markAlertsRead = () => {
        if (unreadAlerts > 0) {
            setUnreadAlerts(0);
            setAlerts(alerts.map(a => ({ ...a, is_read: true })));
        }
    };

    // Send chat message
    const handleSendChat = () => {
        if (!chatInput.trim()) return;

        const newMsg = {
            id: Date.now(),
            sender: 'user' as const,
            message: chatInput.trim(),
            created_at: new Date().toISOString(),
        };

        setChatMessages([...chatMessages, newMsg]);
        setChatInput('');
        showToast('info', 'Message sent to support');
    };

    return (
        <List>
            <Section>
                {user ? (
                    <Cell
                        before={<Avatar src={user.photo_url || ''} size={48} fallbackIcon={<span>👤</span>} />}
                        description={`ID: ${user.id}`}
                        multiline
                    >
                        {user.display_name}
                    </Cell>
                ) : (
                    <Cell>Please log in</Cell>
                )}
            </Section>

            <Section header={`Notifications ${unreadAlerts > 0 ? `(${unreadAlerts})` : ''}`}>
                {alerts.length === 0 ? (
                    <Placeholder description="No notifications yet" />
                ) : (
                    alerts.map(alert => (
                        <Cell
                            key={alert.id}
                            multiline
                            description={new Date(alert.created_at).toLocaleDateString()}
                            onClick={markAlertsRead}
                            before={!alert.is_read && (
                                <div style={{
                                    width: 8,
                                    height: 8,
                                    borderRadius: '50%',
                                    background: 'var(--tg-theme-accent-text-color, var(--tg-theme-button-color))',
                                    marginRight: 8,
                                }} />
                            )}
                        >
                            {alert.message}
                        </Cell>
                    ))
                )}
            </Section>

            <Section header="Live Support">
                <div style={{
                    height: 300,
                    overflowY: 'auto',
                    padding: 10,
                    background: 'var(--tg-theme-secondary-bg-color)',
                }}>
                    {chatMessages.length === 0 ? (
                        <div style={{
                            textAlign: 'center',
                            color: 'var(--tg-theme-hint-color)',
                            padding: 20,
                        }}>
                            Start a conversation with support
                        </div>
                    ) : (
                        chatMessages.map(msg => (
                            <div
                                key={msg.id}
                                style={{
                                    display: 'flex',
                                    justifyContent: msg.sender === 'user' ? 'flex-end' : 'flex-start',
                                    marginBottom: 8,
                                }}
                            >
                                <div style={{
                                    background: msg.sender === 'user'
                                        ? 'var(--tg-theme-button-color)'
                                        : 'var(--tg-theme-bg-color)',
                                    color: msg.sender === 'user'
                                        ? 'var(--tg-theme-button-text-color)'
                                        : 'var(--tg-theme-text-color)',
                                    padding: '8px 12px',
                                    borderRadius: 12,
                                    maxWidth: '80%',
                                    fontSize: 14,
                                }}>
                                    {msg.message}
                                </div>
                            </div>
                        ))
                    )}
                    <div ref={chatEndRef} />
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: 8 }}>
                    <Input
                        placeholder="Type a message..."
                        value={chatInput}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setChatInput(e.target.value)}
                    />
                    <Button
                        size="s"
                        onClick={handleSendChat}
                        disabled={!chatInput.trim()}
                    >
                        Send
                    </Button>
                </div>
            </Section>
        </List>
    );
}
