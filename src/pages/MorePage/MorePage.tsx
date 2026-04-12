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
        user, chatMessages, setChatMessages, showToast, refreshAlerts
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
            showToast('error', 'Failed to send message');
        } finally {
            setIsSending(false);
        }
    };

    const handleOpenLink = (url: string) => {
        if ((window as any).Telegram?.WebApp) {
            (window as any).Telegram.WebApp.openLink(url);
        } else {
            window.open(url, '_blank');
        }
    };

    return (
        <div className="more-page">
            {/* ─── Account Info ─── */}
            <Section header="Account Information">
                <Cell 
                    after={<span style={{ color: 'var(--tg-theme-hint-color)', fontWeight: 'bold' }}>#{user?.id || 'N/A'}</span>}
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        navigator.clipboard.writeText(String(user?.id || ''));
                    }}
                >
                    Your User ID
                </Cell>
            </Section>

            {/* ─── Appearance ─── */}
            <Section header="Appearance">
                <Cell subtitle="Choose application appearance">
                    <div style={{ 
                        display: 'flex', 
                        gap: '8px', 
                        background: 'var(--tg-theme-secondary-bg-color)', 
                        padding: '6px', 
                        borderRadius: '12px',
                        width: '100%',
                        marginTop: '8px'
                    }}>
                        {['light', 'dark', 'auto'].map((t) => (
                            <button
                                key={t}
                                onClick={() => {
                                    import('../../helpers/telegram').then(m => {
                                        m.hapticSelection();
                                        m.setTheme(t === 'auto' ? 'system' : t as 'light' | 'dark');
                                    });
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
                                    boxShadow: themeOverride === t ? '0 2px 4px rgba(0,0,0,0.1)' : 'none',
                                    transition: 'all 0.2s ease',
                                    textTransform: 'capitalize'
                                }}
                            >
                                {t}
                            </button>
                        ))}
                    </div>
                </Cell>
            </Section>

            {/* ─── Contact Us Section ─── */}
            <Section header="Contact Us">
                <Cell 
                    subtitle="Support Email" 
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        window.location.href = 'mailto:Contact@paxyo.com';
                    }}
                >
                    Contact@paxyo.com
                </Cell>
                <Cell 
                    subtitle="Business Email" 
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        window.location.href = 'mailto:Paxyo251@gmail.com';
                    }}
                >
                    Paxyo251@gmail.com
                </Cell>
                <Cell 
                    subtitle="Phone Number" 
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        window.location.href = 'tel:0986411919';
                    }}
                >
                    0986411919
                </Cell>
                <Cell 
                    before={
                        <div style={{ 
                            width: 44, height: 44, margin: 0, borderRadius: 8, 
                            background: 'rgba(42, 171, 238, 0.1)', 
                            display: 'flex', alignItems: 'center', justifyContent: 'center' 
                        }}>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2AABEE" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                            </svg>
                        </div>
                    }
                    subtitle="Click to chat" 
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        handleOpenLink('https://t.me/paxyo');
                    }}
                >
                    @paxyo
                </Cell>
                <Cell 
                    subtitle="Location" 
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        handleOpenLink('https://share.google/VX1Hfdvnofu6zPcch');
                    }}
                >
                    View Office Location 📍
                </Cell>
            </Section>

            {/* ─── Support Chat ─── */}
            <Section header="Live Support Chat">
                <div className="support-card glass-card">
                    <div className="chat-container">
                        <div className="chat-messages" style={{ height: '200px' }}>
                            {chatMessages.length === 0 ? (
                                <div className="chat-empty">
                                    <span>Start a conversation with support</span>
                                </div>
                            ) : (
                                chatMessages.map((msg, i) => (
                                    <div key={i} style={{
                                        alignSelf: msg.is_admin ? 'flex-start' : 'flex-end',
                                        background: msg.is_admin ? 'var(--tg-theme-secondary-bg-color)' : 'var(--tg-theme-button-color)',
                                        color: msg.is_admin ? 'var(--tg-theme-text-color)' : 'var(--tg-theme-button-text-color)',
                                        padding: '10px 14px',
                                        borderRadius: '16px',
                                        maxWidth: '85%',
                                        marginBottom: '8px',
                                        fontSize: '14px'
                                    }}>
                                        {msg.message}
                                    </div>
                                ))
                            )}
                            <div ref={chatEndRef} />
                        </div>
                        <div className="chat-input-row">
                            <input
                                className="chat-input"
                                placeholder="Type a message..."
                                value={chatInput}
                                onChange={(e) => setChatInput(e.target.value)}
                                disabled={isSending}
                            />
                            <button className="chat-send-btn" onClick={handleSendChat} disabled={!chatInput.trim()}>
                                {isSending ? '...' : '➤'}
                            </button>
                        </div>
                    </div>
                </div>
            </Section>

            {/* ─── Terms ─── */}
            <div style={{ textAlign: 'center', marginTop: 16, marginBottom: 32 }}>
                <button 
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        import('sweetalert2').then(Swal => {
                            Swal.default.fire({
                                title: 'Paxyo: About Us',
                                html: `
                                    <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                                        <p style="margin-bottom: 12px;">We don't usually speak. We work behind the scenes.</p>
                                        <p style="margin-bottom: 12px;">We've helped build brands and businesses into successful names in the physical world and now we are doing the same in social media. Many creators, influencers and pages you may know have grown with our support.</p>
                                        <p style="margin-bottom: 12px;">This tool is part of the Paxyo marketing system now made publicly accessible to help everyone grow at an affordable price.</p>
                                        <p style="margin-bottom: 12px;">Our goal is simple: make your page look active and trusted so your content reaches more people, gets noticed and grows faster.</p>
                                        <p style="margin-bottom: 16px;">Please support the team, use the platform responsibly and avoid misuse. We're always open to feedback. 🙏</p>
                                    </div>
                                `,
                                confirmButtonText: 'Got it',
                                confirmButtonColor: '#7C5CFC',
                            });
                        });
                    }}
                    style={{
                        background: 'none',
                        border: 'none',
                        color: 'var(--tg-theme-hint-color)',
                        fontSize: '11px',
                        cursor: 'pointer',
                        textDecoration: 'underline',
                        padding: '4px 8px'
                    }}
                >
                    Terms and Conditions
                </button>
            </div>

            <div style={{ height: 100 }} /> {/* Spacer for bottom bar */}
        </div>
    );
}