import { useState, useCallback, useRef } from 'react';

import { useApp } from '../../context/AppContext';
import { Section, Cell, Button } from '@telegram-apps/telegram-ui';
import { PlatformGrid } from '../../components/PlatformGrid/PlatformGrid';
import { CategoryModal } from '../../components/CategoryModal/CategoryModal';
import { ServiceModal } from '../../components/ServiceModal/ServiceModal';
import { OrderForm, type OrderFormHandle } from '../../components/OrderForm/OrderForm';
import { NewsTicker } from '../../components/NewsTicker/NewsTicker';


export function OrderPage() {
    const {
        recommendedIds, selectedPlatform, selectedCategory, selectedService,
        setSelectedPlatform, setSelectedCategory, setSelectedService,
        showToast
    } = useApp();

    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showServiceModal, setShowServiceModal] = useState(false);
    const [showOrderModal, setShowOrderModal] = useState(false);
    const orderFormRef = useRef<OrderFormHandle | null>(null);

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
        // NEW: Instantly open the order form as soon as a service is picked
        setTimeout(() => setShowOrderModal(true), 150); 
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
            <Section style={{ margin: '0 16px', borderRadius: '14px', overflow: 'hidden' }}>
                <Cell
                    subtitle={selectedCategory || 'Select a category'}
                    onClick={() => {
                        if (selectedPlatform) setShowCategoryModal(true);
                        else import('../../helpers/telegram').then(m => m.hapticNotification('error'));
                    }}
                    // NEW: Shows a green checkmark if completed
                    after={selectedCategory 
                        ? <span style={{color: 'var(--color-success)', fontWeight: 'bold'}}>✓</span> 
                        : <span style={{ color: 'var(--tg-theme-hint-color)' }}>{'>'}</span>
                    }
                >
                    <span style={{ fontWeight: selectedCategory ? 600 : 400 }}>Category</span>
                </Cell>
                
                <div style={{ height: '1px', background: 'var(--surface-glass-border)', marginLeft: '16px' }} />

                <Cell
                    subtitle={selectedService?.name || 'Select a service'}
                    style={{ opacity: selectedCategory ? 1 : 0.4, transition: 'opacity 0.2s' }}
                    onClick={() => {
                        if (selectedCategory) setShowServiceModal(true);
                    }}
                    // NEW: Shows a green checkmark if completed
                    after={selectedService 
                        ? <span style={{color: 'var(--color-success)', fontWeight: 'bold'}}>✓</span> 
                        : <span style={{ color: 'var(--tg-theme-hint-color)' }}>{'>'}</span>
                    }
                >
                    <span style={{ fontWeight: selectedService ? 600 : 400 }}>Service</span>
                </Cell>
            </Section>

            {/* ─── Sticky Call to Action Button ─── */}
            <div style={{ 
                padding: '16px', 
                position: 'sticky', 
                bottom: 0, 
                // Gradient fade so it floats nicely over content
                background: 'linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.9) 30%, #000000 100%)',
                zIndex: 10
            }}>
                <Button
                    size="l"
                    stretched
                    style={{ 
                        // Native Telegram Button Color
                        background: selectedService ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                        // Native Telegram Text Color
                        color: selectedService ? 'var(--tg-theme-button-text-color)' : 'var(--tg-theme-hint-color)',
                        transition: 'all 0.15s ease',
                        fontWeight: 600
                    }}
                    onClick={() => {
                        import('../../helpers/telegram').then(m => {
                            // Safe Validation
                            if (!selectedPlatform) {
                                m.hapticNotification('error');
                                showToast('error', 'Please select a social platform first');
                                return;
                            }
                            if (!selectedCategory) {
                                m.hapticNotification('error');
                                showToast('error', 'Please select a category');
                                return;
                            }
                            if (!selectedService) {
                                m.hapticNotification('error');
                                showToast('error', 'Please select a service');
                                return;
                            }
                            
                            // All safe -> Open Form
                            m.hapticImpact('light');
                            setShowOrderModal(true);
                        });
                    }}
                >
                    {selectedService ? 'Configure Order' : 'Select Service to Order'}
                </Button>
            </div>



            {/* ─── Modals ─── */}
            {showOrderModal && selectedService && (
                <OrderForm 
                    ref={orderFormRef} 
                    onClose={() => setShowOrderModal(false)}
                />
            )}
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
