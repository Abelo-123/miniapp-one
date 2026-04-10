import React, { useState, useMemo, useEffect } from 'react';
import { List, Section, Input, Placeholder } from '@telegram-apps/telegram-ui';
import { onBackButtonClick, showBackButton, hideBackButton } from '@telegram-apps/sdk-react';
import type { Service, SocialPlatform } from '../../types';
import { formatETB } from '../../constants';
import { useAllServices } from '../../hooks/useAllServices';
import { useApp } from '../../context/AppContext';

interface Props {
    onClose: () => void;
}

export function SearchModal({ onClose }: Props) {
    const { setSelectedPlatform, setSelectedCategory, setSelectedService, setActiveTab } = useApp();
    const [search, setSearch] = useState('');
    const { data: services = [], isLoading } = useAllServices();

    useEffect(() => {
        try {
            showBackButton();
            const off = onBackButtonClick(() => {
                onClose();
            });
            return () => {
                off();
                hideBackButton();
            };
        } catch (e) {
            console.error('Back button setup failed', e);
        }
    }, [onClose]);

    const results = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return [];
        const terms = q.split(/\s+/);
        return services.filter(s => {
            const haystack = `${s.name} ${s.category} ${s.id}`.toLowerCase();
            return terms.every(t => haystack.includes(t));
        }).slice(0, 30);
    }, [services, search]);

    const grouped = useMemo(() => {
        const map = new Map<string, Service[]>();
        for (const s of results) {
            const arr = map.get(s.category) || [];
            arr.push(s);
            map.set(s.category, arr);
        }
        return map;
    }, [results]);

    const handleSelectSearchResult = (service: Service) => {
        const textToCheck = (service.category + " " + service.name).toLowerCase();
        
        let network: SocialPlatform = 'other';
        if (textToCheck.includes('youtube') || textToCheck.includes('yt ')) {
            network = 'youtube';
        } else if (textToCheck.includes('tiktok') || textToCheck.includes('tik tok')) {
            network = 'tiktok';
        } else if (textToCheck.includes('telegram') || textToCheck.includes('tg ')) {
            network = 'telegram';
        } else if (textToCheck.includes('instagram') || textToCheck.includes('ig ')) {
            network = 'instagram';
        } else if (textToCheck.includes('twitter') || textToCheck.includes(' x ') || textToCheck.startsWith('x ') || textToCheck.includes('x/')) {
            network = 'twitter';
        } else if (textToCheck.includes('facebook') || textToCheck.includes('fb ')) {
            network = 'facebook';
        } else if (textToCheck.includes('top services') || textToCheck.includes('top ')) {
            network = 'top';
        }

        setSelectedPlatform(network);
        setSelectedCategory(service.category);
        setSelectedService(service);
        setActiveTab('order');
        onClose();
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
            <div style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '16px',
                borderBottom: '1px solid var(--tg-theme-hint-color, #e0e0e0)'
            }}>
                <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 600 }}>🔍 Search</h2>
                <button 
                    onClick={onClose}
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
            <div style={{ flex: 1, overflowY: 'auto', padding: '16px', paddingTop: '0px' }}>
                <List>
                    <Section className="modal-search">
                        <Input
                            inputMode="search"
                            autoComplete="off"
                            spellCheck={false}
                            autoFocus
                            placeholder="Type name, ID, or category..."
                            value={search}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                            className="modal-search-input"
                        />
                    </Section>

                    {isLoading ? (
                        <Section>
                            {[1, 2, 3, 4, 5, 6].map(i => (
                                <div key={i} className="skeleton-row">
                                    <div className="skeleton-bar" style={{ width: '70%' }}></div>
                                    <div className="skeleton-bar" style={{ width: '40%', opacity: 0.6 }}></div>
                                </div>
                            ))}
                        </Section>
                    ) : search.trim() === '' ? (
                        <Placeholder description="Start typing to search" />
                    ) : results.length === 0 ? (
                        <Placeholder description="No services match your search" />
                    ) : (
                        Array.from(grouped.entries()).map(([category, svcs]) => (
                            <Section key={category} header={category}>
                                {svcs.map(svc => (
                                    <div
                                        key={svc.id}
                                        className="modal-item"
                                        onClick={() => handleSelectSearchResult(svc)}
                                    >
                                        <div className="modal-item-main">
                                            <div className="modal-item-name">{svc.name}</div>
                                        </div>
                                        <div className="modal-item-id">ID: {svc.id}</div>
                                        <div className="modal-item-price">{formatETB(svc.rate)} <span style={{ fontSize: '10px', opacity: 0.8 }}>/1000</span></div>
                                    </div>
                                ))}
                            </Section>
                        ))
                    )}
                </List>
            </div>
        </div>
    );
}