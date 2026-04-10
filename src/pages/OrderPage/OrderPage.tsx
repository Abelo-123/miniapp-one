import { useState, useCallback, useEffect, useMemo, useRef } from 'react';
import { formatETB } from '../../constants';

import { useApp } from '../../context/AppContext';
import { Section, Cell, Button } from '@telegram-apps/telegram-ui';
import { PlatformGrid } from '../../components/PlatformGrid/PlatformGrid';
import { CategoryModal } from '../../components/CategoryModal/CategoryModal';
import { ServiceModal } from '../../components/ServiceModal/ServiceModal';
import { NewsTicker } from '../../components/NewsTicker/NewsTicker';
import { hapticImpact, hapticNotification, getInitDataString } from '../../helpers/telegram';


export function OrderPage() {
    const appContext = useApp();
    const {
        user, refreshOrders,
        recommendedIds, selectedPlatform, selectedCategory, selectedService,
        setSelectedPlatform, setSelectedCategory, setSelectedService,
        showToast
    } = appContext;

    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showServiceModal, setShowServiceModal] = useState(false);
    
    const [link, setLink] = useState('');
    const [quantity, setQuantity] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isFirstOrder, setIsFirstOrder] = useState(!localStorage.getItem('hasPlacedOrder'));

    const orderContainerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setLink('');
        setQuantity('');
    }, [selectedService]);

    const totalCharge = useMemo(() => {
        if (!selectedService || !quantity) return 0;
        const q = parseInt(quantity, 10) || 0;
        return (q / 1000) * selectedService.rate;
    }, [selectedService, quantity]);

    const handlePlaceOrder = async () => {
        // 1. Selection Security
        if (!selectedPlatform) {
            showToast('error', 'Select a platform first');
            return hapticNotification('error');
        }
        if (!selectedCategory) {
            showToast('error', 'Select a category first');
            return hapticNotification('error');
        }
        if (!selectedService) {
            showToast('error', 'Select a service first');
            return hapticNotification('error');
        }

        // 2. Input Security
        const q = parseInt(quantity, 10);
        const urlPattern = /^(https?:\/\/|t\.me\/|@)/i;
        
        if (!link.trim() || !urlPattern.test(link.trim())) {
            showToast('error', 'Enter a valid URL or username');
            return hapticNotification('error');
        }
        if (!quantity || isNaN(q)) {
            showToast('error', 'Please enter a valid quantity');
            return hapticNotification('error');
        }
        if (q < selectedService.min) {
            showToast('error', `Min quantity is ${selectedService.min}`);
            return hapticNotification('error');
        }
        if (q > selectedService.max) {
            showToast('error', `Max quantity is ${selectedService.max.toLocaleString()}`);
            return hapticNotification('error');
        }

        // 3. Financial Security
        if (totalCharge > (user?.balance || 0)) {
            showToast('error', 'Insufficient balance. Deposit required.');
            return hapticNotification('error');
        }

        setIsSubmitting(true);
        hapticImpact('medium');

        try {
            const initData = await getInitDataString();
            const res = await fetch(`${import.meta.env.VITE_NODE_API_URL || '/api'}/place-order`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    service_id: (selectedService as any).service || (selectedService as any).id,
                    link: link.trim(),
                    quantity: q,
                    initData
                })
            });
            const data = await res.json();

            if (data.success) {
                hapticNotification('success');
                showToast('success', 'Order placed successfully!');
                
                if (isFirstOrder) {
                    localStorage.setItem('pulseHistoryTab', 'true');
                    window.dispatchEvent(new Event('pulseHistoryTab'));
                }

                localStorage.setItem('hasPlacedOrder', 'true');
                setIsFirstOrder(false);
                setLink('');
                setQuantity('');
                setSelectedService(null);
                
                if ('refreshUser' in appContext && typeof (appContext as any).refreshUser === 'function') {
                    (appContext as any).refreshUser();
                }
                refreshOrders();
            } else {
                hapticNotification('error');
                showToast('error', data.error || 'Failed to place order');
            }
        } catch (e) {
            hapticNotification('error');
            showToast('error', 'Connection failed. Please check your internet.');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handlePlatformSelect = useCallback((platform: typeof selectedPlatform) => {
        setSelectedPlatform(platform);
        setSelectedService(null);
        if (platform === 'top') {
            setSelectedCategory('Top Services');
            setShowServiceModal(true);
        } else {
            setSelectedCategory(null);
            setShowCategoryModal(true);
        }
    }, [setSelectedPlatform, setSelectedCategory, setSelectedService]);

    const handleCategorySelect = useCallback((category: string) => {
        setSelectedCategory(category);
        setSelectedService(null);
        setShowCategoryModal(false);
        setShowServiceModal(true);
    }, [setSelectedCategory, setSelectedService]);

    const handleServiceSelect = useCallback((service: typeof selectedService) => {
        setSelectedService(service);
        setShowServiceModal(false);
        setTimeout(() => {
            if (orderContainerRef.current) {
                orderContainerRef.current.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                window.scrollBy({ top: 300, behavior: 'smooth' });
            }
        }, 150);
    }, [setSelectedService]);

    return (
        <div className="order-page-wrapper">
            <NewsTicker />

            <PlatformGrid
                selectedPlatform={selectedPlatform}
                onSelect={handlePlatformSelect}
            />

            <Section className="selection-section">
                <Cell
                    subtitle={selectedCategory || 'Select a category'}
                    onClick={() => {
                        if (selectedPlatform) {
                            setShowCategoryModal(true);
                        } else {
                            showToast('error', 'Please select a social media platform first (e.g. TikTok, Telegram).');
                            import('../../helpers/telegram').then(m => m.hapticNotification('error'));
                        }
                    }}
                    after={selectedCategory 
                        ? <span style={{color: 'var(--color-success)', fontWeight: 'bold'}}>✓</span> 
                        : <span style={{ color: 'var(--tg-theme-hint-color)' }}>{'>'}</span>
                    }
                >
                    <span style={{ fontWeight: selectedCategory ? 600 : 400 }}>Category</span>
                </Cell>
                
                <div style={{ height: '0.5px', background: 'var(--tg-theme-section-separator-color)', marginLeft: '16px' }} />

                <Cell
                    subtitle={selectedService?.name || 'Select a service'}
                    style={{ opacity: selectedCategory ? 1 : 0.4, transition: 'opacity 0.2s' }}
                    onClick={() => {
                        if (selectedCategory) setShowServiceModal(true);
                    }}
                    after={selectedService 
                        ? <span style={{color: 'var(--color-success)', fontWeight: 'bold'}}>✓</span> 
                        : <span style={{ color: 'var(--tg-theme-hint-color)' }}>{'>'}</span>
                    }
                >
                    <span style={{ fontWeight: selectedService ? 600 : 400 }}>Service</span>
                </Cell>
            </Section>

            {selectedService && (
                <div className="inline-order-container" ref={orderContainerRef}>
                    <div className="order-details-card">
                        <div className="order-details-card__title">
                            <span className="order-details-card__id">#{(selectedService as any).service || (selectedService as any).id}</span>
                            {selectedService.name}
                        </div>
                        <div className="order-details-card__row">
                            <span>Min Order</span>
                            <span className="bold">{selectedService.min}</span>
                        </div>
                        <div className="order-details-card__row">
                            <span>Max Order</span>
                            <span className="bold">{selectedService.max.toLocaleString()}</span>
                        </div>
                        <div className="order-details-card__row">
                            <span>Rate per 1000</span>
                            <span className="bold highlight">{formatETB(selectedService.rate)}</span>
                        </div>
                    </div>

                    <div className="order-input-group">
                        <label>Link / URL</label>
                        <input 
                            type="text" 
                            placeholder="https://" 
                            value={link} 
                            onChange={(e) => setLink(e.target.value)} 
                            className="order-custom-input"
                        />
                    </div>

                    <div className="order-input-group">
                        <label>Quantity</label>
                        <input 
                            type="number" 
                            inputMode="numeric"
                            placeholder={`${selectedService.min} - ${selectedService.max}`} 
                            value={quantity} 
                            onChange={(e) => setQuantity(e.target.value.replace(/\D/g, ''))} 
                            className="order-custom-input"
                        />
                    </div>

                    <div className="order-total-card">
                        <span>Total Charge</span>
                        <span className="order-total-card__amount">
                            {Number(totalCharge).toFixed(4)} ETB
                        </span>
                    </div>

                    <Button
                        size="l"
                        stretched
                        loading={isSubmitting}
                        disabled={isSubmitting || !link || !quantity}
                        className={isFirstOrder && link && quantity ? 'order-btn-pulse' : ''}
                        style={{ 
                            background: 'var(--tg-theme-button-color)',
                            color: 'var(--tg-theme-button-text-color)',
                            fontWeight: 700,
                            borderRadius: '12px',
                            marginTop: '8px',
                            padding: '16px'
                        }}
                        onClick={handlePlaceOrder}
                    >
                        {isSubmitting ? 'Processing...' : `Place Order`}
                    </Button>
                </div>
            )}



            {/* ─── Modals ─── */}
            {showCategoryModal && selectedPlatform && (
                <CategoryModal
                    platform={selectedPlatform}
                    onSelect={handleCategorySelect}
                    onClose={() => setShowCategoryModal(false)}
                />
            )}

            {showServiceModal && selectedCategory && (
                <ServiceModal
                    category={selectedCategory}
                    recommendedIds={recommendedIds}
                    onSelect={handleServiceSelect}
                    onClose={() => setShowServiceModal(false)}
                />
            )}
        </div>
    );
}