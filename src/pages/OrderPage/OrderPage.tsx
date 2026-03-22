import { useState, useMemo, useCallback, useRef } from 'react';
import { useApp } from '../../context/AppContext';
import { PLATFORMS, formatETB } from '../../constants';
import { PlatformGrid } from '../../components/PlatformGrid/PlatformGrid';
import { CategoryModal } from '../../components/CategoryModal/CategoryModal';
import { ServiceModal } from '../../components/ServiceModal/ServiceModal';
import { OrderForm, type OrderFormHandle } from '../../components/OrderForm/OrderForm';
import { SearchModal } from '../../components/SearchModal/SearchModal';

export function OrderPage() {
    const {
        services, recommendedIds, selectedPlatform, selectedCategory, selectedService,
        setSelectedPlatform, setSelectedCategory, setSelectedService,
        discountPercent, holidayName, marqueeText, user, isLoading,
    } = useApp();

    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showServiceModal, setShowServiceModal] = useState(false);
    const [showSearchModal, setShowSearchModal] = useState(false);
    const orderFormRef = useRef<OrderFormHandle | null>(null);

    const platformCategories = useMemo(() => {
        if (!selectedPlatform) return [];
        if (selectedPlatform === 'top') return ['Top Services'];

        const platformDef = PLATFORMS.find(p => p.id === selectedPlatform);
        if (!platformDef) return [];

        const allCategories = [...new Set(services.map(s => s.category))];

        if (selectedPlatform === 'other') {
            const majorKeywords = PLATFORMS
                .filter(p => p.id !== 'other' && p.id !== 'top')
                .flatMap(p => p.keywords);
            return allCategories.filter(cat => {
                const lower = cat.toLowerCase();
                return !majorKeywords.some(kw => lower.includes(kw));
            });
        }

        return allCategories.filter(cat => {
            const lower = cat.toLowerCase();
            return platformDef.keywords.some(kw => lower.includes(kw));
        });
    }, [selectedPlatform, services]);

    const categoryServices = useMemo(() => {
        if (!selectedCategory) return [];
        if (selectedCategory === 'Top Services') {
            return services.filter(s => recommendedIds.includes(s.id));
        }
        return services.filter(s => s.category === selectedCategory);
    }, [selectedCategory, services, recommendedIds]);

    const handlePlatformSelect = useCallback((platform: typeof selectedPlatform) => {
        setSelectedPlatform(platform);
        if (platform === 'top') {
            setSelectedCategory('Top Services');
            setShowServiceModal(true);
        } else {
            setShowCategoryModal(true);
        }
    }, [setSelectedPlatform, setSelectedCategory]);

    const handleCategorySelect = useCallback((category: string) => {
        setSelectedCategory(category);
        setShowCategoryModal(false);
        setShowServiceModal(true);
    }, [setSelectedCategory]);

    const handleServiceSelect = useCallback((service: typeof selectedService) => {
        setSelectedService(service);
        setShowServiceModal(false);
    }, [setSelectedService]);

    const handleSearchResultSelect = useCallback((service: typeof services[0]) => {
        const cat = service.category.toLowerCase();
        const platform = PLATFORMS.find(p => p.keywords.some(kw => cat.includes(kw)));
        setSelectedPlatform(platform?.id || 'other');
        setSelectedCategory(service.category);
        setSelectedService(service);
        setShowSearchModal(false);
    }, [setSelectedPlatform, setSelectedCategory, setSelectedService]);

    return (
        <div className="order-page-wrapper">
            {/* ─── Welcome Header ─── */}
            <div className="welcome-header">
                <div className="welcome-header__content">
                    {user?.photo_url ? (
                        <img
                            className="welcome-header__avatar"
                            src={user.photo_url}
                            alt={user.first_name}
                        />
                    ) : (
                        <div className="welcome-header__avatar-fallback">👤</div>
                    )}
                    <div className="welcome-header__text">
                        <div className="welcome-header__greeting">
                            Hey {user?.first_name || 'User'}! 👋
                        </div>
                        <div className="welcome-header__brand">Paxyo SMM</div>
                    </div>
                    <button
                        className="welcome-header__search"
                        onClick={() => setShowSearchModal(true)}
                    >
                        🔍
                    </button>
                </div>
            </div>

            {/* ─── Balance Pill ─── */}
            {user && (
                <div className="balance-pill">
                    💎 {formatETB(user.balance)}
                </div>
            )}

            {/* ─── Announcement ─── */}
            {marqueeText && (
                <div className="announcement-banner">
                    <span className="announcement-banner__icon">📢</span>
                    <span className="announcement-banner__text">{marqueeText}</span>
                </div>
            )}

            {/* ─── Discount Badge ─── */}
            {discountPercent > 0 && (
                <div className="discount-badge">
                    <span className="discount-badge__icon">🔥</span>
                    <div>
                        <span className="discount-badge__text">{discountPercent}% Discount Active</span>
                        <span className="discount-badge__sub">• {holidayName}</span>
                    </div>
                </div>
            )}

            {/* ─── Platform Selection ─── */}
            <div className="paxyo-section-header">Select Platform</div>
            <PlatformGrid
                selectedPlatform={selectedPlatform}
                onSelect={handlePlatformSelect}
            />

            {/* ─── Current Selection ─── */}
            {selectedCategory && (
                <>
                    <div className="paxyo-section-header">Selection</div>
                    <div
                        className="selection-card"
                        onClick={() => setShowCategoryModal(true)}
                    >
                        <div className="selection-card__left">
                            <div className="selection-card__label">Category</div>
                            <div className="selection-card__value">{selectedCategory}</div>
                        </div>
                        <span className="selection-card__action">Change ›</span>
                    </div>

                    {selectedService && (
                        <div
                            className="selection-card"
                            onClick={() => setShowServiceModal(true)}
                        >
                            <div className="selection-card__left">
                                <div className="selection-card__label">Service</div>
                                <div className="selection-card__value">{selectedService.name}</div>
                            </div>
                            <span className="selection-card__action">Change ›</span>
                        </div>
                    )}
                </>
            )}

            {/* ─── Order Form ─── */}
            {selectedService && <OrderForm ref={orderFormRef} />}
            {selectedService && (
                <div style={{ padding: '0 16px', marginTop: 12 }}>
                    <button
                        className="order-page__quick-order"
                        onClick={() => orderFormRef.current?.submit()}
                    >
                        Order Now
                    </button>
                </div>
            )}

            {/* ─── Modals ─── */}
            {showCategoryModal && (
                <CategoryModal
                    categories={platformCategories}
                    onSelect={handleCategorySelect}
                    onClose={() => setShowCategoryModal(false)}
                    isLoading={isLoading}
                />
            )}

            {showServiceModal && (
                <ServiceModal
                    services={categoryServices}
                    onSelect={handleServiceSelect}
                    onClose={() => setShowServiceModal(false)}
                    isLoading={isLoading}
                />
            )}

            {showSearchModal && (
                <SearchModal
                    services={services}
                    onSelect={handleSearchResultSelect}
                    onClose={() => setShowSearchModal(false)}
                />
            )}
        </div>
    );
}
