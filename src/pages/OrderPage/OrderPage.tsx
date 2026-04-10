import { useState, useCallback, useEffect, useMemo } from 'react';
import { formatETB } from '../../constants';

import { useApp } from '../../context/AppContext';
import { Section, Cell, Button } from '@telegram-apps/telegram-ui';
import { PlatformGrid } from '../../components/PlatformGrid/PlatformGrid';
import { CategoryModal } from '../../components/CategoryModal/CategoryModal';
import { ServiceModal } from '../../components/ServiceModal/ServiceModal';
import { NewsTicker } from '../../components/NewsTicker/NewsTicker';
import { hapticImpact, hapticNotification, getInitDataString } from '../../helpers/telegram';


export function OrderPage() {
    const {
        user, refreshUser, refreshOrders,
        recommendedIds, selectedPlatform, selectedCategory, selectedService,
        setSelectedPlatform, setSelectedCategory, setSelectedService,
        showToast
    } = useApp();

    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showServiceModal, setShowServiceModal] = useState(false);
    
    const [link, setLink] = useState('');
    const [quantity, setQuantity] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isFirstOrder, setIsFirstOrder] = useState(!localStorage.getItem('hasPlacedOrder'));

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
        if (!selectedService) return;
        
        const q = parseInt(quantity, 10);
        if (!link.trim()) return showToast('error', 'Please enter a valid link');
        if (!q || q < selectedService.min) return showToast('error', `Minimum quantity is ${selectedService.min}`);
        if (q > selectedService.max) return showToast('error', `Maximum quantity is ${selectedService.max}`);
        if (totalCharge > (user?.balance || 0)) return showToast('error', 'Insufficient balance. Please add funds.');

        setIsSubmitting(true);
        hapticImpact('medium');

        try {
            const initData = await getInitDataString();
            const res = await fetch(`${import.meta.env.VITE_NODE_API_URL || '/api'}/place-order`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    service_id: selectedService.service,
                    link: link.trim(),
                    quantity: q,
                    initData
                })
            });
            const data = await res.json();

            if (data.success) {
                hapticNotification('success');
                showToast('success', 'Order placed successfully!');
                
                localStorage.setItem('hasPlacedOrder', 'true');
                setIsFirstOrder(false);
                setLink('');
                setQuantity('');
                setSelectedService(null);
                
                refreshUser();
                refreshOrders();
            } else {
                hapticNotification('error');
                showToast('error', data.error || 'Failed to place order');
            }
        } catch (e) {
            hapticNotification('error');
            showToast('error', 'Network error occurred');
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
        setTimeout(() => window.scrollBy({ top: 300, behavior: 'smooth' }), 100);
    }, [setSelectedService]);

    return (
        <div className="order-page-wrapper">
            {/* ─── Global Marquee Banner ─── */}
            <NewsTicker />

            {/* ─── Platform Selection Grid ─── */}
            <PlatformGrid
                selectedPlatform={selectedPlatform}
                onSelect={handlePlatformSelect}
            />

            {/* ─── Category & Service Selection ─── */}
            <Section className="selection-section">
                <Cell
                    subtitle={selectedCategory || 'Select a category'}
                    onClick={() => {
                        if (selectedPlatform) setShowCategoryModal(true);
                        else import('../../helpers/telegram').then(m => m.hapticNotification('error'));
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
                <div className="inline-order-container">
                    <div className="order-details-card">
                        <div className="order-details-card__title">
                            <span className="order-details-card__id">#{selectedService.service}</span>
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
