import { useState, useMemo, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import { PLATFORMS, formatETB } from '../../constants';
import { PlatformGrid } from '../../components/PlatformGrid/PlatformGrid';
import { CategoryModal } from '../../components/CategoryModal/CategoryModal';
import { ServiceModal } from '../../components/ServiceModal/ServiceModal';
import { OrderForm } from '../../components/OrderForm/OrderForm';
import { SearchModal } from '../../components/SearchModal/SearchModal';
import { hapticSelection } from '../../helpers/telegram';
import './OrderPage.css';

export function OrderPage() {
    const {
        services, recommendedIds, selectedPlatform, selectedCategory, selectedService,
        setSelectedPlatform, setSelectedCategory, setSelectedService,
        discountPercent, holidayName, marqueeText, user,
        setActiveTab,
    } = useApp();

    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showServiceModal, setShowServiceModal] = useState(false);
    const [showSearchModal, setShowSearchModal] = useState(false);

    // Filter categories (Memoized)
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

    // Filter services (Memoized)
    const categoryServices = useMemo(() => {
        if (!selectedCategory) return [];
        if (selectedCategory === 'Top Services') {
            return services.filter(s => recommendedIds.includes(s.id));
        }
        return services.filter(s => s.category === selectedCategory);
    }, [selectedCategory, services, recommendedIds]);

    // Handlers
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
        <div className="order-page">
            {/* ── Header Bar ── */}
            <div className="op-header">
                <div className="op-header-left">
                    <div className="op-logo">
                        <span className="op-logo-icon">🚀</span>
                    </div>
                    <div className="op-header-info">
                        <span className="op-brand">Paxyo</span>
                        <span className="op-balance">
                            Balance {user ? formatETB(user.balance) : '...'}
                        </span>
                    </div>
                </div>
                <div className="op-header-actions">
                    <button
                        className="op-icon-btn"
                        onClick={() => setShowSearchModal(true)}
                        aria-label="Search"
                    >
                        🔍
                    </button>
                    <button
                        className="op-icon-btn op-notif-btn"
                        onClick={() => {
                            hapticSelection();
                            setActiveTab('more');
                        }}
                        aria-label="Notifications"
                    >
                        🔔
                    </button>
                </div>
            </div>

            {/* ── Marquee / Announcements ── */}
            {marqueeText && (
                <div className="op-marquee">
                    <div className="op-marquee-inner">{marqueeText}</div>
                </div>
            )}

            {/* ── Discount Banner ── */}
            {discountPercent > 0 && (
                <div className="op-discount-banner">
                    🔥 {discountPercent}% Off — {holidayName}
                </div>
            )}

            {/* ── Platform Selector ── */}
            <div className="op-platforms">
                <PlatformGrid
                    selectedPlatform={selectedPlatform}
                    onSelect={handlePlatformSelect}
                />
            </div>

            {/* ── Category Dropdown ── */}
            <div className="op-section-label">1. Category</div>
            <button
                className="op-dropdown"
                onClick={() => {
                    if (selectedPlatform) {
                        hapticSelection();
                        setShowCategoryModal(true);
                    }
                }}
                disabled={!selectedPlatform}
            >
                <span className="op-dropdown-icon">📂</span>
                <span className="op-dropdown-text">
                    {selectedCategory || 'Select Category'}
                </span>
                <span className="op-dropdown-arrow">▾</span>
            </button>

            {/* ── Service Dropdown ── */}
            <div className="op-section-label">2. Service</div>
            <button
                className="op-dropdown"
                onClick={() => {
                    if (selectedCategory) {
                        hapticSelection();
                        setShowServiceModal(true);
                    }
                }}
                disabled={!selectedCategory}
            >
                <span className="op-dropdown-icon">📋</span>
                <span className="op-dropdown-text">
                    {selectedService ? selectedService.name : 'Select Service'}
                </span>
                <span className="op-dropdown-arrow">▾</span>
            </button>

            {/* ── Order Form (inline, below dropdowns) ── */}
            {selectedService && <OrderForm />}

            {/* ── Static Order Button when no service selected ── */}
            {!selectedService && (
                <button
                    className="op-order-btn"
                    disabled
                >
                    Order
                </button>
            )}

            {/* ── Modals ── */}
            {showCategoryModal && (
                <CategoryModal
                    categories={platformCategories}
                    onSelect={handleCategorySelect}
                    onClose={() => setShowCategoryModal(false)}
                />
            )}

            {showServiceModal && (
                <ServiceModal
                    services={categoryServices}
                    onSelect={handleServiceSelect}
                    onClose={() => setShowServiceModal(false)}
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
