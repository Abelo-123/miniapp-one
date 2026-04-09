import React, { useState, useMemo, useEffect, useRef, forwardRef, useImperativeHandle } from 'react';
import { Section, Input, Textarea, Button, Cell } from '@telegram-apps/telegram-ui';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useApp } from '../../context/AppContext';
import { formatETB, getLinkPlaceholder, QUANTITY_STEP } from '../../constants';
import * as api from '../../api';
import type { Order } from '../../types';
import {
    hapticImpact,
    hapticNotification,
    configureMainButton,
    showMainButton,
    hideMainButton,
    onMainButtonClick,
    enableClosingConfirmation,
    disableClosingConfirmation,
} from '../../helpers/telegram';

// URL validation helper
function isValidUrl(str: string): boolean {
    if (!str.trim()) return false;
    const urlPattern = /^(https?:\/\/|t\.me\/|telegram\.me\/|@)/i;
    return urlPattern.test(str.trim());
}

export type OrderFormHandle = { submit: () => Promise<void> };
export type OrderFormProps = { onClose?: () => void };
export const OrderForm = forwardRef<OrderFormHandle, OrderFormProps>(function OrderForm({ onClose }, ref) {
    const {
        selectedService, selectedPlatform, selectedCategory,
        rateMultiplier, discountPercent,
        user, userCanOrder, isTelegramApp,
        setBalance, 
        showToast, setActiveTab,
    } = useApp();

    const queryClient = useQueryClient();

    // Form State
    const [link, setLink] = useState('');
    const [quantity, setQuantity] = useState('');
    const [comments, setComments] = useState('');
    const [answerNumber, setAnswerNumber] = useState('');
    
    const [touched, setTouched] = useState<{
        link?: boolean;
        quantity?: boolean;
        comments?: boolean;
        answerNumber?: boolean;
    }>({});
    
    const [errors, setErrors] = useState<{
        link?: string;
        quantity?: string;
        comments?: string;
        answerNumber?: string;
        general?: string;
    }>({});

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
    }, [quantity, service.rate, rateMultiplier, discountPercent, service.type]);

    const placeholder = getLinkPlaceholder(selectedCategory || '', selectedPlatform || 'other');
    const showComments = ['Custom Comments', 'Custom Comments Package', 'Mentions with Hashtags'].includes(service.type);
    const showQuantity = service.type !== 'Package';
    const showPoll = service.type === 'Poll';

    // Validation with error messages
    const validation = useMemo(() => {
        const errs: typeof errors = {};
        
        if (!link.trim()) {
            errs.link = 'Link is required';
        } else if (!isValidUrl(link.trim())) {
            errs.link = 'Please enter a valid URL or username';
        }
        
        if (showQuantity && !showComments) {
            const qty = parseInt(quantity);
            if (!quantity || isNaN(qty)) {
                errs.quantity = 'Quantity is required';
            } else if (qty < service.min) {
                errs.quantity = `Minimum quantity is ${service.min}`;
            } else if (qty > service.max) {
                errs.quantity = `Maximum quantity is ${service.max.toLocaleString()}`;
            } else if (qty % QUANTITY_STEP !== 0) {
                errs.quantity = `Quantity must be in multiples of ${QUANTITY_STEP}`;
            }
        }
        
        if (showComments) {
            const lines = comments.split('\n').filter((l: string) => l.trim());
            if (lines.length === 0) {
                errs.comments = 'Please enter at least one comment';
            } else if (lines.length < service.min) {
                errs.comments = `Minimum ${service.min} comments required`;
            } else if (lines.length > service.max) {
                errs.comments = `Maximum ${service.max} comments allowed`;
            }
        }
        
        if (showPoll) {
            const ans = parseInt(answerNumber);
            if (!answerNumber || isNaN(ans)) {
                errs.answerNumber = 'Answer number is required';
            }
        }
        
        if (user && charge > user.balance) {
            errs.general = `Insufficient balance. You have ${formatETB(user.balance)} but need ${formatETB(charge)}`;
        }
        
        if (!userCanOrder) {
            errs.general = 'Ordering is currently disabled. Please try again later.';
        }

        const visibleErrs: typeof errors = {};
        if (errs.general) visibleErrs.general = errs.general;
        if (touched.link && errs.link) visibleErrs.link = errs.link;
        if (touched.quantity && errs.quantity) visibleErrs.quantity = errs.quantity;
        if (touched.comments && errs.comments) visibleErrs.comments = errs.comments;
        if (touched.answerNumber && errs.answerNumber) visibleErrs.answerNumber = errs.answerNumber;
        
        return {
            isValid: Object.keys(errs).length === 0,
            errors: visibleErrs,
            rawErrors: errs,
        };
    }, [link, quantity, comments, answerNumber, service, charge, user, userCanOrder, showComments, showQuantity, showPoll, touched]);

    const isValid = validation.isValid;

    // Optimistic Mutation Engine
    const { mutate: placeOrderMutation, isPending: submitting } = useMutation({
        mutationFn: api.placeOrder,
        onMutate: async () => {
            await queryClient.cancelQueries({ queryKey: [ 'orders' ] });
            const previousOrders = queryClient.getQueryData<Order[]>([ 'orders' ]) || [];
            const previousBalance = user?.balance || 0;
            const qty = service.type === 'Package' ? service.min : parseInt(quantity);
            const optimisticOrder: any = {
                id: Date.now(),
                api_order_id: 0,
                service_id: service.id,
                service_name: service.name,
                link: link,
                quantity: qty,
                charge: charge,
                status: 'pending',
                remains: qty,
                start_count: 0,
                created_at: new Date().toISOString(),
                isOptimistic: true
            };
            queryClient.setQueryData([ 'orders' ], (old: Order[] = []) => [ optimisticOrder, ...old ]);
            if (user) setBalance(user.balance - charge);
            return { previousOrders, previousBalance };
        },
        onError: (err: any, _vars, context) => {
            if (context) {
                queryClient.setQueryData([ 'orders' ], context.previousOrders);
                setBalance(context.previousBalance);
            }
            showToast('error', err.message || 'Order failed. Reverting...');
            hapticNotification('error');
        },
        onSuccess: (response) => {
            if (response.success) {
                queryClient.setQueryData([ 'orders' ], (old: any[] = []) => 
                    old.map(o => o.isOptimistic ? { ...o, id: response.order_id, api_order_id: response.order_id, isOptimistic: false } : o)
                );
                showToast('success', response.verified ? 'Order confirmed!' : 'Order processing.');
                hapticImpact('heavy');
                hapticNotification('success');
                setLink(''); setQuantity(''); setComments(''); setAnswerNumber(''); setTouched({});
                if (onClose) onClose();
                setActiveTab('history');
            } else {
                throw new Error(response.error || 'Failed to place order');
            }
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: [ 'orders' ] });
        }
    });

    const handleSubmit = async () => {
        setTouched({ link: true, quantity: true, comments: true, answerNumber: true });
        if (!validation.isValid) {
            hapticNotification('error');
            const firstError = Object.values(validation.rawErrors)[0];
            showToast('error', firstError);
            return;
        }
        const qty = service.type === 'Package' ? service.min : parseInt(quantity);
        placeOrderMutation({
            service: service.id,
            link,
            quantity: qty,
            tg_id: user?.id,
            comments: comments || undefined,
            answer_number: answerNumber ? parseInt(answerNumber) : undefined,
        });
    };

    // Imperative submit binding for non-Telegram path
    useImperativeHandle(ref, () => ({ submit: handleSubmit }), [handleSubmit]);
    // MainButton Effect — all via SDK helpers
    const handleSubmitRef = useRef(handleSubmit);
    useEffect(() => { handleSubmitRef.current = handleSubmit; }, [handleSubmit]);

    useEffect(() => {
        if (!isTelegramApp) return;

        const handleMainBtnClick = () => handleSubmitRef.current();

        if (submitting) {
            configureMainButton({
                text: 'PLACING ORDER...',
                isEnabled: true,
                isLoaderVisible: true,
            });
        } else if (isValid) {
            configureMainButton({
                text: `PAY ${formatETB(charge)}`,
                color: '#2ecc71',
                textColor: '#ffffff',
                isEnabled: true,
                isLoaderVisible: false,
            });
        } else {
            let text = 'FILL DETAILS';
            if (!link.trim()) text = 'ENTER LINK';
            else if (user && charge > user.balance) text = 'INSUFFICIENT FUNDS';
            else text = 'INVALID QUANTITY';

            configureMainButton({
                text,
                color: '#95a5a6',
                textColor: '#ffffff',
                isEnabled: false,
                isLoaderVisible: false,
            });
        }

        showMainButton();
        const off = onMainButtonClick(handleMainBtnClick);

        return () => {
            off();
            hideMainButton();
        };
    }, [isTelegramApp, isValid, submitting, charge, link, user]);

    // Closing Confirmation via SDK — protect unsaved form data
    useEffect(() => {
        if ((link || quantity) && !submitting) {
            enableClosingConfirmation();
        } else {
            disableClosingConfirmation();
        }
        return () => disableClosingConfirmation();
    }, [link, quantity, submitting]);

    // Auto-scroll to form when service changes (e.g. from search)
    const containerRef = useRef<HTMLDivElement>(null);
    useEffect(() => {
        if (containerRef.current) {
            setTimeout(() => {
                containerRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        }
    }, [service.id]);

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal-sheet modal-sheet--large" style={{ paddingBottom: '24px' }} onClick={e => e.stopPropagation()}>
                <div className="modal-header" style={{ position: 'sticky', top: 0, zIndex: 10, background: 'var(--tg-theme-bg-color, #1a1a2e)' }}>
                    <span className="modal-title">Configure Order</span>
                    <Button mode="plain" style={{ padding: 0 }} onClick={onClose}>✕</Button>
                </div>
                
                <div style={{ maxHeight: '80vh', overflowY: 'auto', paddingBottom: '80px' }} ref={containerRef}>
                    <Section
                header={`Selected Service: ${service.name}`}
                footer={user && charge > user.balance ? `Insufficient Balance (Have: ${formatETB(user.balance)})` : `Total Charge: ${formatETB(charge)}`}
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
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setLink(e.target.value);
                    }}
                    status={validation.errors.link ? 'error' : 'default'}
                />
                {validation.errors.link && (
                    <div className="order-form-error">{validation.errors.link}</div>
                )}

                {showQuantity && !showComments && (
                    <>
                        <Input
                            header="Quantity"
                            placeholder={`Min: ${service.min} - Max: ${service.max.toLocaleString()}`}
                            type="number"
                            value={quantity}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                setQuantity(e.target.value);
                            }}
                            status={validation.errors.quantity ? 'error' : 'default'}
                        />
                        {validation.errors.quantity && (
                            <div className="order-form-error">{validation.errors.quantity}</div>
                        )}
                    </>
                )}

                {showComments && (
                    <>
                        <Textarea
                            header="Comments (One per line)"
                            placeholder="Enter list of comments..."
                            value={comments}
                            onChange={(e: any) => {
                                setComments(e.target.value);
                            }}
                            status={validation.errors.comments ? 'error' : 'default'}
                        />
                        {validation.errors.comments && (
                            <div className="order-form-error">{validation.errors.comments}</div>
                        )}
                        <div className="order-form-hint">
                            {comments.split('\n').filter((l: string) => l.trim()).length} / {service.max} comments
                        </div>
                    </>
                )}

                {showPoll && (
                    <>
                        <Input
                            header="Answer Number"
                            type="number"
                            value={answerNumber}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                setAnswerNumber(e.target.value);
                            }}
                            status={validation.errors.answerNumber ? 'error' : 'default'}
                        />
                        {validation.errors.answerNumber && (
                            <div className="order-form-error">{validation.errors.answerNumber}</div>
                        )}
                    </>
                )}
            </Section>

            {/* General Error Message */}
            {validation.errors.general && (
                <div className="order-form-error order-form-error--general">
                    {validation.errors.general}
                </div>
            )}

            {/* Fallback Button for Modal */}
            <Section>
                <Button
                    size="l"
                    stretched
                    disabled={submitting}
                    onClick={handleSubmit}
                    loading={submitting}
                >
                    {submitting ? 'Ordering...' : `Pay ${formatETB(charge)} `}
                </Button>
            </Section>
            </div>
        </div>
    </div>
    );
});
