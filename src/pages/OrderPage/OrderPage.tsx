import { useState, useMemo, useCallback } from 'react';
import { List, Section, Cell, Avatar, Button, Banner } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';
import { PLATFORMS } from '../../constants';
import { PlatformGrid } from '../../components/PlatformGrid/PlatformGrid';
import { CategoryModal } from '../../components/CategoryModal/CategoryModal';
import { ServiceModal } from '../../components/ServiceModal/ServiceModal';
import { OrderForm } from '../../components/OrderForm/OrderForm';
import { SearchModal } from '../../components/SearchModal/SearchModal';
// Styles handled by TUI components

export function OrderPage() {
    const {
        services, recommendedIds, selectedPlatform, selectedCategory, selectedService,
        setSelectedPlatform, setSelectedCategory, setSelectedService,
        discountPercent, holidayName, marqueeText, user,
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
        <div className="order-page-wrapper">
            <List>
                {/* Header Section */}
                <Section>
                    <Cell
                        before={<Avatar src={user?.photo_url || ''} size={48} fallbackIcon={<span>👤</span>} />}
                        description={`Hey ${user?.first_name || 'User'}!`}
                        after={
                            <Button mode="plain" size="s" onClick={() => setShowSearchModal(true)}>
                                🔍 Search
                            </Button>
                        }
                    >
                        Paxyo SMM
                    </Cell>
                </Section>

                {/* Marquee Banner */}
                {marqueeText && (
                    <Banner
                        header="Announcement"
                        description={marqueeText}
                    >
                        {/* Optional Action */}
                    </Banner>
                )}

                {/* Discount Banner */}
                {discountPercent > 0 && (
                    <Banner
                        header={`${discountPercent}% Discount Active`}
                        description={`Holiday Special: ${holidayName}`}
                        type="section"
                    />
                )}

                {/* Platform Grid */}
                <Section header="Select Platform">
                    <div style={{ padding: '0 16px 16px' }}>
                        <PlatformGrid
                            selectedPlatform={selectedPlatform}
                            onSelect={handlePlatformSelect}
                        />
                    </div>
                </Section>

                {/* Selection Info */}
                {selectedCategory && (
                    <Section header="Selection">
                        <Cell
                            onClick={() => setShowCategoryModal(true)}
                            after={<Button mode="plain" size="s">Change</Button>}
                            description="Category"
                        >
                            {selectedCategory}
                        </Cell>

                        {selectedService && (
                            <Cell
                                onClick={() => setShowServiceModal(true)}
                                after={<Button mode="plain" size="s">Change</Button>}
                                description="Service"
                                multiline
                            >
                                {selectedService.name}
                            </Cell>
                        )}
                    </Section>
                )}

                {/* Order Form (Returns Fragments of Sections) */}
                {selectedService && <OrderForm />}
            </List>

            {/* Modals */}
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
