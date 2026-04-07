import { useState, useRef, useEffect } from 'react';
import { useApp } from '../../context/AppContext';
import { Section, Cell, Switch } from '@telegram-apps/telegram-ui';
import * as api from '../../api';

export function MorePage() {
    const {
        chatMessages, setChatMessages, showToast, refreshAlerts
    } = useApp();

    const [chatInput, setChatInput] = useState('');
    const [isDarkTheme, setIsDarkTheme] = useState(true);
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
                <Cell
                    before={
                        <div className="theme-card__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="12" cy="12" r="5" />
                                <line x1="12" y1="1" x2="12" y2="3" />
                                <line x1="12" y1="21" x2="12" y2="23" />
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                                <line x1="1" y1="12" x2="3" y2="12" />
                                <line x1="21" y1="12" x2="23" y2="12" />
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                            </svg>
                        </div>
                    }
                    after={<Switch checked={isDarkTheme} onChange={(e: any) => setIsDarkTheme(e.target.checked)} />}
                    subtitle="Switch to light/dark mode"
                    Component="label"
                >
                    App Theme
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
                                        <div
                                            key={msg.id}
                                            className={`chat-bubble ${isAdmin ? 'chat-bubble--admin' : 'chat-bubble--user'}`}
                                        >
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
