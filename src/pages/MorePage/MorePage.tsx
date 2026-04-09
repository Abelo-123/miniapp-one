import { useState, useRef, useEffect } from 'react';
import { useApp } from '../../context/AppContext';
import { Section, Cell } from '@telegram-apps/telegram-ui';
import * as api from '../../api';

interface MorePageProps {
    themeOverride: 'auto' | 'light' | 'dark';
    setThemeOverride: (t: 'auto' | 'light' | 'dark') => void;
}

export function MorePage({ themeOverride, setThemeOverride }: MorePageProps) {
    const {
        chatMessages, setChatMessages, showToast, refreshAlerts
    } = useApp();

    const [chatInput, setChatInput] = useState('');

    const [isSending, setIsSending] = useState(false);
    const chatEndRef = useRef<HTMLDivElement>(null);

    const loadMessages = async () => {
        try {
            const data = await api.fetchChat();
            if (data && data.success) {
                setChatMessages(data.messages);
            }
        } catch (e) {
            console.error(e);
        }
    };

    // Scroll chat to bottom
    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [chatMessages]);

    useEffect(() => {
        loadMessages();
        refreshAlerts();
        const interval = setInterval(() => {
            loadMessages();
            refreshAlerts();
        }, 5000);
        return () => clearInterval(interval);
    }, [refreshAlerts]);

    // Send chat message
    const handleSendChat = async () => {
        if (!chatInput.trim() || isSending) return;

        setIsSending(true);
        const text = chatInput.trim();
        setChatInput('');

        try {
            await api.sendChat(text);
            await loadMessages();
            showToast('info', 'Message sent to support');
        } catch (e) {
            console.error('Failed to send message', e);
            showToast('error', 'Failed to send message');
        } finally {
            setIsSending(false);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendChat();
        }
    };

    return (
        <div className="more-page">
            <Section>
                <Cell subtitle="Choose application appearance">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                        <span style={{ fontWeight: 600 }}>App Theme</span>
                    </div>
                    <div style={{ 
                        display: 'flex', 
                        gap: '8px', /* THIS FIXES THE SQUISHING */
                        background: 'var(--tg-theme-secondary-bg-color)', 
                        padding: '6px', 
                        borderRadius: '12px',
                        width: '100%' 
                    }}>
                        {['auto', 'light', 'dark'].map((t) => (
                            <button
                                key={t}
                                onClick={() => {
                                    import('../../helpers/telegram').then(m => m.hapticSelection());
                                    setThemeOverride(t as any);
                                    localStorage.setItem('app-theme', t);
                                }}
                                style={{
                                    flex: 1,
                                    padding: '8px 0',
                                    background: themeOverride === t ? 'var(--tg-theme-bg-color)' : 'transparent',
                                    color: themeOverride === t ? 'var(--tg-theme-text-color)' : 'var(--tg-theme-hint-color)',
                                    border: 'none',
                                    borderRadius: '8px',
                                    fontWeight: themeOverride === t ? 600 : 500,
                                    boxShadow: themeOverride === t ? '0 2px 4px rgba(0,0,0,0.05)' : 'none',
                                    transition: 'all 0.2s ease',
                                    textTransform: 'capitalize'
                                }}
                            >
                                {t}
                            </button>
                        ))}
                    </div>
                </Cell>
                
                <Cell
                    before={
                        <div className="refer-card__icon" style={{ width: 44, height: 44, margin: 0, borderRadius: 8 }}>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                    }
                    subtitle="Coming soon to the platform"
                >
                    Refer & Earn
                </Cell>
            </Section>

            {/* ─── Live Support ─── */}
            <Section header="Live Support">
                <div className="support-card glass-card">
                    <div className="support-card__header">
                        <div className="support-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#25D366" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                        </div>
                        <div className="support-card__info">
                            <span className="support-card__title">Chat with Support</span>
                            <span className="support-card__status">
                                <span className="support-card__status-dot" />
                                ONLINE
                            </span>
                        </div>
                    </div>
                    <div className="chat-container">
                        <div className="chat-messages">
                            {chatMessages.length === 0 ? (
                                <div className="chat-empty">
                                    <span className="chat-empty__icon">💬</span>
                                    <span>Start a conversation with support</span>
                                </div>
                            ) : (
                                chatMessages.map(msg => {
                                    const isAdmin = msg.is_admin === 1 || msg.is_admin === true;
                                    return (
                                <div key={msg.id} style={{
                                    alignSelf: isAdmin ? 'flex-start' : 'flex-end',
                                    background: isAdmin ? 'var(--tg-theme-secondary-bg-color)' : 'var(--tg-theme-button-color)',
                                    color: isAdmin ? 'var(--tg-theme-text-color)' : 'var(--tg-theme-button-text-color)',
                                    padding: '10px 14px',
                                    borderRadius: '16px',
                                    borderBottomLeftRadius: isAdmin ? '4px' : '16px',
                                    borderBottomRightRadius: isAdmin ? '16px' : '4px',
                                    maxWidth: '80%',
                                    marginBottom: '8px',
                                    fontSize: '14px',
                                    lineHeight: '1.4'
                                }}>
                                    {msg.message}
                                </div>
                                    );
                                })
                            )}
                            <div ref={chatEndRef} />
                        </div>
                        <div className="chat-input-row">
                            <input
                                className="chat-input"
                                placeholder="Type a message..."
                                value={chatInput}
                                onChange={(e) => setChatInput(e.target.value)}
                                onKeyDown={handleKeyDown}
                                disabled={isSending}
                            />
                            <button
                                className="chat-send-btn"
                                onClick={handleSendChat}
                                disabled={!chatInput.trim() || isSending}
                            >
                                {isSending ? '...' : '➤'}
                            </button>
                        </div>
                    </div>
                </div>
            </Section>

            {/* Bottom spacing */}
            <div style={{ height: 24 }} />
        </div>
    );
}
