import { useState, useMemo, useCallback, useEffect, useRef } from 'react';
import { Section, Input, Textarea, Button, Cell } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';
import { formatETB, getLinkPlaceholder, QUANTITY_STEP } from '../../constants';

export function OrderForm() {
    const {
        selectedService, selectedPlatform, selectedCategory,
        rateMultiplier, discountPercent,
        user, userCanOrder, isTelegramApp, showToast,
        setBalance, setOrders, orders,
    } = useApp();

    // Form State
    const [link, setLink] = useState('');
    const [quantity, setQuantity] = useState('');
    const [comments, setComments] = useState('');
    const [answerNumber, setAnswerNumber] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const service = selectedService!;

    // Memoized calculations
    const { charge, hasDiscount } = useMemo(() => {
        const qty = parseInt(quantity) || 0;
        const original = (qty / 1000) * service.rate * rateMultiplier;
        const discounted = discountPercent > 0 ? original * (1 - discountPercent / 100) : original;
        return {
            charge: discounted,
            hasDiscount: discountPercent > 0,
        };
    }, [quantity, service.rate, rateMultiplier, discountPercent]);

    // Handle Comments -> Quantity
    const handleCommentsChange = useCallback((e: any) => {
        const val = e.target.value;
        setComments(val);
        if (['Custom Comments', 'Custom Comments Package', 'Mentions with Hashtags'].includes(service.type)) {
            const lines = val.split('\n').filter((l: string) => l.trim());
            setQuantity(String(lines.length));
        }
    }, [service.type]);

    const placeholder = getLinkPlaceholder(selectedCategory || '', selectedPlatform || 'other');
    const showComments = ['Custom Comments', 'Custom Comments Package', 'Mentions with Hashtags'].includes(service.type);
    const showQuantity = service.type !== 'Package';
    const showPoll = service.type === 'Poll';

    // Validation Check
    const isValid = useMemo(() => {
        if (!link.trim()) return false;
        const qty = service.type === 'Package' ? service.min : parseInt(quantity);
        if (!qty || isNaN(qty)) return false;
        if (qty < service.min || qty > service.max) return false;
        if (qty % QUANTITY_STEP !== 0) return false;
        if (user && charge > user.balance) return false;
        if (!userCanOrder) return false;
        return true;
    }, [link, quantity, service, charge, user, userCanOrder]);

    // Submit Handler
    const handleSubmit = async () => {
        if (!isValid) return;

        setSubmitting(true);
        try {
            // Mock API call
            await new Promise(r => setTimeout(r, 1200));

            const qty = service.type === 'Package' ? service.min : parseInt(quantity);
            const newOrder = {
                id: orders.length + 100,
                api_order_id: 90000 + orders.length,
                service_id: service.id,
                service_name: service.name,
                link,
                quantity: qty,
                charge,
                status: 'pending' as const,
                remains: qty,
                start_count: 0,
                created_at: new Date().toISOString(),
            };

            setOrders([newOrder, ...orders]);
            if (user) setBalance(user.balance - charge);
            showToast('success', `Order #${newOrder.api_order_id} placed!`);

            // Haptic feedback via SDK
            try {
                (window as any).Telegram?.WebApp?.HapticFeedback?.impactOccurred('heavy');
            } catch { /* ignore */ }

            // Reset
            setLink('');
            setQuantity('');
            setComments('');
            setAnswerNumber('');
        } catch {
            showToast('error', 'Failed to place order');
        } finally {
            setSubmitting(false);
        }
    };

    // MainButton Effect
    const handleSubmitRef = useRef(handleSubmit);
    useEffect(() => { handleSubmitRef.current = handleSubmit; }, [handleSubmit]);

    useEffect(() => {
        if (!isTelegramApp) return;

        const tg = (window as any).Telegram?.WebApp;
        if (!tg) return;
        const mb = tg.MainButton;

        const handleMainBtnClick = () => handleSubmitRef.current();

        const updateButton = () => {
            if (submitting) {
                mb.enable();
                mb.showProgress(true);
                mb.setText('PLACING ORDER...');
            } else {
                mb.hideProgress();
                if (isValid) {
                    mb.enable();
                    mb.setParams({
                        text: `PAY ${formatETB(charge)} `,
                        color: '#2ecc71',
                        text_color: '#ffffff'
                    });
                } else {
                    mb.disable();
                    mb.setParams({
                        text: 'FILL DETAILS',
                        color: '#95a5a6',
                        text_color: '#ffffff'
                    });

                    if (!link.trim()) mb.setText('ENTER LINK');
                    else if (user && charge > user.balance) mb.setText('INSUFFICIENT FUNDS');
                    else mb.setText('INVALID QUANTITY');
                }
            }
        };

        updateButton();
        mb.show();

        mb.onClick(handleMainBtnClick);

        return () => {
            mb.offClick(handleMainBtnClick);
            mb.hide();
            mb.hideProgress();
        };
    }, [isTelegramApp, isValid, submitting, charge, link, user]);

    // Cleanup Closing Confirmation
    useEffect(() => {
        return () => {
            const tg = (window as any).Telegram?.WebApp;
            if (tg) tg.disableClosingConfirmation();
        };
    }, []);

    // Closing Confirmation Logic
    useEffect(() => {
        const tg = (window as any).Telegram?.WebApp;
        if (!tg) return;
        if ((link || quantity) && !submitting) {
            tg.enableClosingConfirmation();
        } else {
            tg.disableClosingConfirmation();
        }
    }, [link, quantity, submitting]);

    return (
        <>
            <Section
                header={`Selected Service: ${service.name} `}
                footer={user && charge > user.balance ? `Insufficient Balance(Have: ${formatETB(user.balance)})` : `Total Charge: ${formatETB(charge)} `}
            >
                <Cell
                    subtitle={`${service.min} - ${service.max.toLocaleString()} • Rate: ${formatETB(service.rate)} `}
                    multiline
                >
                    {service.category}
                </Cell>

                {hasDiscount && (
                    <Cell before="🔥" after={`- ${discountPercent}% `}>
                        Discount applied
                    </Cell>
                )}
            </Section>

            <Section header="Order Details">
                <Input
                    header="Link"
                    placeholder={placeholder}
                    value={link}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setLink(e.target.value)}
                    status={!link.trim() && link.length > 0 ? 'error' : 'default'}
                />

                {showQuantity && !showComments && (
                    <Input
                        header="Quantity"
                        placeholder={`Min: ${service.min} `}
                        type="number"
                        value={quantity}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setQuantity(e.target.value)}
                    />
                )}

                {showComments && (
                    <Textarea
                        header="Comments (One per line)"
                        placeholder="Enter list of comments..."
                        value={comments}
                        onChange={handleCommentsChange}
                    />
                )}

                {showPoll && (
                    <Input
                        header="Answer Number"
                        type="number"
                        value={answerNumber}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setAnswerNumber(e.target.value)}
                    />
                )}
            </Section>

            {/* Fallback Button for Non-Telegram Environment */}
            {!isTelegramApp && (
                <Section>
                    <Button
                        size="l"
                        stretched
                        disabled={!isValid || submitting}
                        onClick={handleSubmit}
                        loading={submitting}
                    >
                        {submitting ? 'Ordering...' : `Pay ${formatETB(charge)} `}
                    </Button>
                </Section>
            )}
        </>
    );
}
