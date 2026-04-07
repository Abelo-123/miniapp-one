import { useState, useEffect, useRef } from 'react';
import { useApp } from '../../context/AppContext';
import { Input, Button } from '@telegram-apps/telegram-ui';
import * as api from '../../api';
import type { ChatMessage } from '../../types';

interface Props {
    onBack: () => void;
}

export function ChatPanel({ onBack }: Props) {
    const { chatMessages, setChatMessages, refreshAlerts } = useApp();
    const [newMessage, setNewMessage] = useState('');
    const [isSending, setIsSending] = useState(false);
    const scrollRef = useRef<HTMLDivElement>(null);

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
        loadMessages();
        refreshAlerts();
        const interval = setInterval(() => {
            loadMessages();
            refreshAlerts();
        }, 5000);
        return () => clearInterval(interval);
    }, [refreshAlerts]);

    useEffect(() => {
        if (scrollRef.current) {
            scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
        }
    }, [chatMessages]);

    const handleSend = async () => {
        if (!newMessage.trim() || isSending) return;
        
        setIsSending(true);
        const text = newMessage.trim();
        setNewMessage('');

        // Optimistic update
        const tempMsg: ChatMessage = {
            id: Date.now(),
            user_id: 'me', // placeholder for UI
            is_admin: 0,
            message: text,
            created_at: new Date().toISOString(),
        };
        setChatMessages([...chatMessages, tempMsg]);

        try {
            await api.sendChat(text);
            await loadMessages();
        } catch (e) {
            console.error('Failed to send message', e);
        } finally {
            setIsSending(false);
        }
    };

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
            {/* Header */}
            <div style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '16px',
                borderBottom: '1px solid var(--tg-theme-hint-color, #e0e0e0)',
                background: 'var(--tg-theme-bg-color, #ffffff)',
            }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <div style={{
                        width: '36px', height: '36px', borderRadius: '50%',
                        background: 'linear-gradient(135deg, #7c5cfc, #5b8def)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        color: 'white', fontWeight: 'bold'
                    }}>
                        A
                    </div>
                    <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 600 }}>Support Chat</h2>
                </div>
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

            {/* Chat Area */}
            <div 
                ref={scrollRef}
                style={{ flex: 1, overflowY: 'auto', padding: '16px', display: 'flex', flexDirection: 'column', gap: '12px', background: 'var(--tg-theme-secondary-bg-color, #f4f4f5)' }}
            >
                {chatMessages.length === 0 ? (
                    <div style={{ textAlign: 'center', color: 'var(--tg-theme-hint-color, #999)', marginTop: '40px' }}>
                        Send a message to Support.
                    </div>
                ) : (
                    chatMessages.map((msg: any) => {
                        const isAdmin = msg.is_admin === 1 || msg.is_admin === true || msg.sender === 'admin';
                        return (
                            <div key={msg.id} style={{
                                alignSelf: isAdmin ? 'flex-start' : 'flex-end',
                                background: isAdmin ? 'var(--tg-theme-bg-color, #fff)' : 'var(--accent, #7c5cfc)',
                                color: isAdmin ? 'var(--tg-theme-text-color, #000)' : '#fff',
                                padding: '10px 14px',
                                borderRadius: '16px',
                                borderBottomLeftRadius: isAdmin ? '4px' : '16px',
                                borderBottomRightRadius: isAdmin ? '16px' : '4px',
                                maxWidth: '75%',
                                boxShadow: '0 2px 5px rgba(0,0,0,0.05)',
                                fontSize: '14px',
                                lineHeight: '1.4'
                            }}>
                                <div style={{ marginBottom: '4px', wordBreak: 'break-word' }}>
                                    {msg.message}
                                </div>
                                <div style={{
                                    fontSize: '10px',
                                    textAlign: 'right',
                                    opacity: isAdmin ? 0.5 : 0.8
                                }}>
                                    {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                </div>
                            </div>
                        );
                    })
                )}
            </div>

            {/* Input Form */}
            <div style={{
                padding: '12px 16px',
                borderTop: '1px solid var(--tg-theme-hint-color, #e0e0e0)',
                background: 'var(--tg-theme-bg-color, #ffffff)',
                display: 'flex', gap: '8px', alignItems: 'center'
            }}>
                <div style={{ flex: 1 }}>
                    <Input
                        value={newMessage}
                        onChange={(e: any) => setNewMessage(e.target.value)}
                        onKeyDown={(e: any) => e.key === 'Enter' && handleSend()}
                        placeholder="Type a message..."
                        disabled={isSending}
                        style={{ border: 'none', background: 'var(--tg-theme-secondary-bg-color, #f4f4f5)', borderRadius: '20px', padding: '12px 16px' }}
                    />
                </div>
                <Button 
                    size="s"
                    onClick={handleSend}
                    disabled={!newMessage.trim() || isSending}
                    style={{ background: 'var(--accent, #7c5cfc)', color: '#fff', borderRadius: '50%', width: '40px', height: '40px', padding: 0, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </Button>
            </div>
        </div>
    );
}
