import { useState, useCallback, useRef } from 'react';
import { formatETB } from '../../constants';

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

            <div style={{ 
                display: 'flex', 
                justifyContent: 'space-between', 
                padding: '0 20px 16px',
                color: 'var(--tg-theme-hint-color)',
                fontSize: '12px',
                textTransform: 'uppercase',
                letterSpacing: '0.5px'
            }}>
                <span>Balance: <strong style={{color: 'var(--tg-theme-text-color)'}}>{formatETB(useApp().user?.balance || 0)}</strong></span>
                <span onClick={() => useApp().setActiveTab('deposit')} style={{cursor: 'pointer', color: 'var(--tg-theme-link-color)'}}>+ Add Funds</span>
            </div>

            {/* ─── Platform Selection Grid ─── */}
            <PlatformGrid
                selectedPlatform={selectedPlatform}
                onSelect={handlePlatformSelect}
            />

            {/* ─── Category & Service Selection ─── */}
            <Section 
                style={{ 
                    margin: '16px', 
                    borderRadius: '12px', 
                    background: 'var(--tg-theme-bg-color)',
                    border: '1px solid var(--tg-theme-section-separator-color)',
                    overflow: 'hidden'
                }}
            >
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

            {/* ─── Sticky Action Bar ─── */}
            <div style={{ 
                padding: '12px 16px', 
                background: 'var(--tg-theme-bg-color)',
                borderTop: '1px solid var(--tg-theme-section-separator-color)',
                position: 'sticky',
                bottom: 0,
                zIndex: 99
            }}>
                <Button
                    size="l"
                    stretched
                    disabled={!selectedService}
                    style={{ 
                        background: selectedService ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                        color: selectedService ? 'var(--tg-theme-button-text-color)' : 'var(--tg-theme-hint-color)',
                        transition: 'all 0.2s ease',
                        fontWeight: 700,
                        borderRadius: '12px'
                    }}
                    onClick={() => {
                        import('../../helpers/telegram').then(m => {
                            if (!selectedService) {
                                m.hapticNotification('error');
                                showToast('error', 'Please select a service first');
                                return;
                            }
                            m.hapticImpact('light');
                            setShowOrderModal(true);
                        });
                    }}
                >
                    {selectedService 
                        ? `Order for ${formatETB(selectedService.rate)}` 
                        : 'Select Service First'
                    }
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
