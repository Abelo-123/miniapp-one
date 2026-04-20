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
    const chatContainerRef = useRef<HTMLDivElement>(null);

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
        if (chatContainerRef.current) {
            chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight;
        }
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
            {/* ─── User ID ─── */}
            <Section header="User ID">
                <Cell 
                    after={
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <span style={{ color: 'var(--tg-theme-hint-color)', fontWeight: 'bold' }}>#{user?.id || 'N/A'}</span>
                            <i 
                                className="fa fa-copy" 
                                style={{ fontSize: '14px', color: 'var(--tg-theme-hint-color)', cursor: 'pointer' }}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    import('../../helpers/telegram').then(m => m.hapticSelection());
                                    navigator.clipboard.writeText(String(user?.id || ''));
                                    showToast('success', 'User ID copied!');
                                }}
                            />
                        </div>
                    }
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        navigator.clipboard.writeText(String(user?.id || ''));
                        showToast('success', 'User ID copied!');
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

            {/* ─── Live Support Chat ─── */}
            <Section header="Live Support Chat">
                <div className="support-card glass-card">
                    <div className="chat-container">
                        <div className="chat-messages" style={{ height: '200px' }} ref={chatContainerRef}>
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

            {/* ─── Promote Telegram Channel ─── */}
            <Section header="Promote">
                <Cell 
                    before={
                        <div style={{ 
                            width: 44, height: 44, margin: 0, borderRadius: 8, 
                            background: 'linear-gradient(135deg, #2AABEE 0%, #229ED9 100%)', 
                            display: 'flex', alignItems: 'center', justifyContent: 'center' 
                        }}>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                    }
                    subtitle="Join our channel for updates"
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        handleOpenLink('https://t.me/paxyo251');
                    }}
                >
                    @paxyo251
                </Cell>
            </Section>

            {/* ─── About Us ─── */}
            <Section header="About Us">
                <Cell 
                    subtitle="Learn more about Paxyo"
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        import('sweetalert2').then(Swal => {
                            Swal.default.fire({
                                title: 'About Paxyo',
                                html: `
                                    <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                                        <p style="margin-bottom: 12px;">We don't usually speak. We work behind the scenes.</p>
                                        <p style="margin-bottom: 12px;">We've helped build brands and businesses into successful names in the physical world and now we are doing the same in social media. Many creators, influencers and pages you may know have grown with our support.</p>
                                        <p style="margin-bottom: 12px;">This tool is part of the Paxyo marketing system now made publicly accessible to help everyone grow at an affordable price.</p>
                                        <p style="margin-bottom: 12px;">Our goal is simple make your page look active and trusted so your content reaches more people gets noticed and grows faster.</p>
                                        <p style="margin-bottom: 16px;">Please support the team use the platform responsibly and avoid misuse. We're always open to feedback. 🙏</p>
                                    </div>
                                `,
                                confirmButtonText: 'Got it',
                                confirmButtonColor: '#7C5CFC',
                            });
                        });
                    }}
                >
                    Learn more about us
                </Cell>
            </Section>

            {/* ─── Contact Us ─── */}
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

            {/* ─── Terms & Conditions ─── */}
            <div style={{ textAlign: 'center', marginTop: 16, marginBottom: 32 }}>
                <button 
                    onClick={() => {
                        import('../../helpers/telegram').then(m => m.hapticSelection());
                        import('sweetalert2').then(Swal => {
                            Swal.default.fire({
                                title: 'Terms & Conditions',
                                html: `
                                    <div style="text-align: left; font-size: 13px; line-height: 1.6; max-height: 400px; overflow-y: auto; padding-right: 8px;">
                                        <p style="margin-bottom: 12px;"><strong>1. Introduction</strong><br>Welcome to Paxyo. By accessing or using our services, you agree to be bound by these Terms & Conditions. These terms apply to all users of the platform. If you do not agree with any part of these terms, you should not use Paxyo.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>2. Description of Services</strong><br>Paxyo provides digital marketing and social media engagement services, including likes, views, followers, and other engagement metrics.<br><br>Paxyo operates as a licensed marketing service provider in Ethiopia and is not affiliated with any social media platforms.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>3. User Responsibilities</strong><br>Users agree to:<br>- Provide accurate and valid order details (links, usernames, etc.)<br>- Ensure accounts or content are public and accessible<br>- Not use Paxyo for illegal, harmful, or misleading purposes<br><br>Paxyo is not responsible for issues caused by incorrect information provided by the user.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>4. Orders & Processing</strong><br>- Orders begin after payment is successfully completed.<br>- Orders cannot be canceled or modified once processing has started.<br>- Delivery time may vary depending on the service and demand.<br>- Some services may start instantly, while others may take time.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>5. Service Performance Disclaimer</strong><br>- Paxyo does not guarantee permanent results for all services.<br>- Social media platforms may update their systems, which can affect delivery.<br>- Drops in followers, likes, or views may occur over time.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>6. Refund & Balance Policy</strong><br>- Paxyo does not provide refunds to bank accounts, mobile money, or external payment methods.<br>- If an order is partially completed, canceled, or fails, the remaining amount will be returned to the user's Paxyo balance.<br>- Users can reuse this balance to place new orders at any time.<br>- Paxyo balance cannot be withdrawn as cash.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>7. Data Privacy & Security</strong><br>- Paxyo respects user privacy and does not sell, rent, or share user data with third parties.<br>- User information is stored using secure systems and modern encryption practices to protect against unauthorized access.<br>- Sensitive data is handled with strict internal controls and is only accessible when necessary for system operation or support.<br>- Paxyo team members do not access personal user data unless required to resolve a support issue.<br>- We continuously improve our security measures to keep user data safe.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>8. Pricing & Payments</strong><br>- All prices are displayed inside the platform and may change at any time.<br>- Users are responsible for reviewing service details before placing orders.<br>- Paxyo is not responsible for issues caused by third-party payment providers.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>9. Account & Security</strong><br>- Users are responsible for maintaining the security of their accounts.<br>- Paxyo is not liable for unauthorized access due to user negligence.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>10. Prohibited Activities</strong><br>Users must not:<br>- Use Paxyo for fraudulent or deceptive purposes<br>- Attempt to exploit or harm the system<br>- Use automation or bots to abuse services<br><br>Violations may result in account suspension.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>11. Account Suspension & Termination</strong><br>Paxyo reserves the right to suspend or terminate accounts that violate these terms or engage in suspicious activity.<br><br>No refunds will be provided in such cases.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>12. Limitation of Liability</strong><br>Paxyo is not responsible for:<br>- Any loss of profits, data, or business opportunities<br>- Social media account restrictions or bans<br>- Any indirect damages resulting from service use<br><br>All services are used at the user's own risk.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>13. Service Availability</strong><br>Paxyo aims to provide continuous service but does not guarantee uninterrupted operation. Temporary downtime may occur due to maintenance or technical issues.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>14. Contact & Support</strong><br>Paxyo is a licensed marketing company in Ethiopia. Users can contact us at any time through our official support channels.</p>
                                        
                                        <p style="margin-bottom: 12px;"><strong>15. Changes to Terms</strong><br>Paxyo may update these Terms & Conditions at any time. Continued use of the platform means you accept any changes.</p>
                                        
                                        <p style="margin-bottom: 0;"><em>Last updated: March 2026</em></p>
                                    </div>
                                `,
                                confirmButtonText: 'Close',
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