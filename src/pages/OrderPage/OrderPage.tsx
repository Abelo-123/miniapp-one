import { useState, useEffect, useMemo, useRef } from 'react';
import { formatETB, getServiceRequirements } from '../../constants';
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
        selectedPlatform, selectedCategory, selectedService,
        setSelectedPlatform, setSelectedCategory, setSelectedService,
        showToast, discountPercent
    } = appContext;

    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showServiceModal, setShowServiceModal] = useState(false);
    
    const [link, setLink] = useState('');
    const [quantity, setQuantity] = useState('');
    const [comments, setComments] = useState('');
    const [answerNumber, setAnswerNumber] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isFirstOrder, setIsFirstOrder] = useState(!localStorage.getItem('hasPlacedOrder'));

    const orderContainerRef = useRef<HTMLDivElement>(null);

    const reqs = useMemo(() => {
        return getServiceRequirements(selectedService, selectedPlatform || 'other');
    }, [selectedService, selectedPlatform]);

    const showComments = reqs.mode === 'comments' || reqs.mode === 'hashtags';
    const showPoll = reqs.mode === 'poll';
    const showPackage = reqs.mode === 'package';
    const showQuantity = !showPackage && !showComments;

    useEffect(() => {
        setLink('');
        setQuantity(selectedService?.type === 'Package' ? (selectedService.min?.toString() || '') : '');
        setComments('');
        setAnswerNumber('');
    }, [selectedService]);

    const effectiveQuantity = useMemo(() => {
        if (!selectedService) return 0;
        if (selectedService.type === 'Package') return selectedService.min;
        if (showComments) {
            return comments.split('\n').filter(l => l.trim()).length;
        }
        return parseInt(quantity, 10) || 0;
    }, [selectedService, quantity, comments, showComments]);

    const totalCharge = useMemo(() => {
        if (!selectedService || effectiveQuantity <= 0) return 0;
        const original = (effectiveQuantity / 1000) * selectedService.rate;
        return discountPercent > 0 ? original * (1 - (discountPercent / 100)) : original;
    }, [selectedService, effectiveQuantity, discountPercent]);





    const handlePlaceOrder = async () => {
        // 1. Selection Security
        if (!selectedPlatform) {
            showToast('error', 'Action Required: Select a platform first');
            return hapticNotification('error');
        }
        if (!selectedCategory) {
            showToast('error', 'Action Required: Select a category first');
            return hapticNotification('error');
        }
        if (!selectedService) {
            showToast('error', 'Action Required: Select a service first');
            return hapticNotification('error');
        }

        // 2. Input Security
        const q = effectiveQuantity;
        
        if (!link.trim()) {
            showToast('error', 'Missing Link: Please enter the link or username for this order.');
            return hapticNotification('error');
        }

        if (showComments && q === 0) {
            showToast('error', 'Missing Comments: Please enter at least one comment line.');
            return hapticNotification('error');
        }

        if (showPoll && (!answerNumber || isNaN(parseInt(answerNumber, 10)))) {
            showToast('error', 'Missing Poll Option: Please enter the answer number.');
            return hapticNotification('error');
        }

        if (showQuantity && (!quantity || isNaN(q))) {
            showToast('error', 'Missing Quantity: Please specify how many interactions you want to order.');
            return hapticNotification('error');
        }
        if (q < selectedService.min) {
            showToast('error', `Quantity Too Low: The minimum order requirement for this service is ${selectedService.min}.`);
            return hapticNotification('error');
        }
        if (q > selectedService.max) {
            showToast('error', `Quantity Exceeds Limit: The maximum order limit for this service is ${selectedService.max.toLocaleString()}.`);
            return hapticNotification('error');
        }
        if (showQuantity && q % 10 !== 0) {
            showToast('error', 'Invalid Quantity: The quantity must be a multiple of 10.');
            return hapticNotification('error');
        }

        // 3. Financial Security
        if (totalCharge > (user?.balance || 0)) {
            showToast('error', 'Action Required: Insufficient balance. Please deposit funds.');
            return hapticNotification('error');
        }

        setIsSubmitting(true);
        hapticImpact('medium');

        try {
            const initData = await getInitDataString();
            const res = await fetch(`${import.meta.env.VITE_NODE_API_URL || '/api'}/orders/place`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    service: (selectedService as any).service || (selectedService as any).id,
                    link: link.trim(),
                    quantity: q,
                    initData,
                    comments: showComments && comments.trim() ? comments.trim() : undefined,
                    answer_number: showPoll && answerNumber ? parseInt(answerNumber, 10) : undefined,
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
                setComments('');
                setAnswerNumber('');
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
                            <span className="bold highlight">
                                {formatETB(selectedService.rate)}
                                <span style={{ fontSize: '10px', opacity: 0.5, marginLeft: '8px', fontWeight: 400 }}>
                                    ({selectedService.original_rate} * {appContext.rateMultiplier})
                                </span>
                            </span>
                        </div>
                    </div>

                    <div className="order-input-group">
                        <label>{reqs.labelLink}</label>
                        <input 
                            type="text" 
                            placeholder={reqs.placeholderLink} 
                            value={link} 
                            onChange={(e) => setLink(e.target.value)} 
                            className="order-custom-input"
                        />
                    </div>

                    {showComments && (
                        <div className="order-input-group">
                            <label>{reqs.labelExtra || 'Comments (One per line)'} • <span style={{ color: 'var(--color-accent)' }}>{effectiveQuantity} entered</span></label>
                            <textarea 
                                placeholder={reqs.placeholderExtra || "Enter comments here...\nGood post!\nAmazing!\nLove this!"}
                                value={comments} 
                                onChange={(e) => setComments(e.target.value)} 
                                rows={5}
                                style={{ width: '100%', padding: '12px', borderRadius: '8px', background: 'var(--tg-theme-secondary-bg-color, #252542)', color: 'var(--tg-theme-text-color)', border: '1px solid var(--border-input, rgba(255,255,255,0.1))', outline: 'none', fontSize: '14px', resize: 'vertical' }}
                            />
                            <div style={{ fontSize: '12px', color: 'var(--tg-theme-hint-color)', marginTop: '4px' }}>
                                Min: {selectedService.min} • Max: {selectedService.max} lines
                            </div>
                        </div>
                    )}

                    {showPoll && (
                        <div className="order-input-group">
                            <label>{reqs.labelExtra || 'Poll Option / Answer Number'}</label>
                            <input 
                                type="number" 
                                placeholder={reqs.placeholderExtra || "e.g. 1 (for first option), 2 (for second option)"} 
                                value={answerNumber} 
                                onChange={(e) => setAnswerNumber(e.target.value.replace(/\D/g, ''))} 
                                className="order-custom-input"
                            />
                        </div>
                    )}

                    {showPackage && (
                        <div className="order-input-group">
                            <label>Quantity</label>
                            <div style={{ padding: '12px', background: 'rgba(0,214,143,0.1)', color: 'var(--color-success)', borderRadius: '8px', fontWeight: 600, fontSize: '14px' }}>
                                📦 Fixed Package Quantity: {selectedService.min}
                            </div>
                        </div>
                    )}

                    {showQuantity && (
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
                    )}


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
                        disabled={isSubmitting}
                        className={isFirstOrder ? 'order-btn-pulse' : ''}
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