import { useState, useCallback, useRef } from 'react';
import Swal from 'sweetalert2';
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

            {/* ─── Category Selection ─── */}
            <Section>
                <Cell
                    subtitle={selectedCategory || 'Select a category'}
                    onClick={() => {
                        if (selectedPlatform) {
                            setShowCategoryModal(true);
                        } else {
                            Swal.fire({
                                title: 'Select Platform',
                                text: 'Please tap a social platform (YouTube, TikTok, etc.) above before picking a category.',
                                icon: 'info',
                                confirmButtonColor: '#7c5cfc'
                            });
                        }
                    }}
                    after={<span style={{ color: 'var(--tg-theme-hint-color)' }}>{'>'}</span>}
                >
                    Category
                </Cell>
            </Section>

            <Section>
                <Cell
                    subtitle={selectedService?.name || 'Select a service'}
                    onClick={() => {
                        if (selectedCategory) {
                            setShowServiceModal(true);
                        } else if (!selectedPlatform) {
                            Swal.fire({
                                title: 'Start with Platform',
                                text: 'Select a social platform and category first!',
                                icon: 'info',
                                confirmButtonColor: '#7c5cfc'
                            });
                        } else {
                            Swal.fire({
                                title: 'Select Category',
                                text: 'Please choose a category from the list before selecting a specific service.',
                                icon: 'info',
                                confirmButtonColor: '#7c5cfc'
                            });
                        }
                    }}
                    after={<span style={{ color: 'var(--tg-theme-hint-color)' }}>{'>'}</span>}
                >
                    Service
                </Cell>
            </Section>

            {/* ─── Order Trigger ─── */}
            <div style={{ padding: '20px 16px' }}>
                <Button
                    size="l"
                    stretched
                    onClick={() => {
                        if (!selectedPlatform) {
                            Swal.fire({ title: 'Hold On!', text: 'Please select a platform (e.g., Telegram, TikTok) first.', icon: 'warning', confirmButtonColor: '#f39c12' });
                            return;
                        }
                        if (!selectedCategory) {
                            Swal.fire({ title: 'Hold On!', text: 'Please select a category.', icon: 'warning', confirmButtonColor: '#f39c12' });
                            return;
                        }
                        if (!selectedService) {
                            Swal.fire({ title: 'Hold On!', text: 'Please select a specific service to order.', icon: 'warning', confirmButtonColor: '#f39c12' });
                            return;
                        }
                        setShowOrderModal(true);
                    }}
                    style={{ background: 'linear-gradient(135deg, #7c5cfc 0%, #5b8def 100%)', color: '#fff' }}
                >
                    Configure Order
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
