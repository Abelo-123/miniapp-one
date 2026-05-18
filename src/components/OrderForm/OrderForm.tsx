import React, { useState, useMemo, useEffect, useRef, forwardRef, useImperativeHandle } from 'react';
import { Section, Input, Textarea, Button, Cell } from '@telegram-apps/telegram-ui';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useApp } from '../../context/AppContext';
import { formatETB, getLinkPlaceholder, QUANTITY_STEP, getServiceRequirements } from '../../constants';
import * as api from '../../api';
import type { Order, CustomField } from '../../types';
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
import { onBackButtonClick, showBackButton, hideBackButton } from '@telegram-apps/sdk-react';

// URL validation helper
function isValidUrl(str: string): boolean {
    if (!str.trim()) return false;
    const urlPattern = /^(https?:\/\/|t\.me\/|telegram\.me\/|@)/i;
    return urlPattern.test(str.trim());
}

const FIELD_TYPES: CustomField['type'][] = ['text', 'link', 'comment', 'hashtag', 'note'];
const FIELD_TYPE_LABELS: Record<CustomField['type'], string> = {
    text: '✏️ Text',
    link: '🔗 Link',
    comment: '💬 Comment',
    hashtag: '#️⃣ Hashtag',
    note: '📝 Note',
};

export type OrderFormHandle = { submit: () => Promise<void> };
export type OrderFormProps = { onClose?: () => void };
export const OrderForm = forwardRef<OrderFormHandle, OrderFormProps>(function OrderForm({ onClose }, ref) {
    const {
        selectedService, selectedPlatform,
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
    const [customFields, setCustomFields] = useState<CustomField[]>([]);

    
    const [touched, setTouched] = useState<{
        link?: boolean;
        quantity?: boolean;
        comments?: boolean;
        answerNumber?: boolean;
    }>({});
    
    type FormErrors = {
        link?: string;
        quantity?: string;
        comments?: string;
        answerNumber?: string;
        general?: string;
    };

    const service = selectedService!;

    const reqs = useMemo(() => {
        return getServiceRequirements(service, selectedPlatform || 'other');
    }, [service, selectedPlatform]);

    const showComments = reqs.mode === 'comments' || reqs.mode === 'hashtags';
    const showPackage = reqs.mode === 'package';
    const showPoll = reqs.mode === 'poll';
    const showQuantity = !showPackage && !showComments;

    const effectiveQuantity = useMemo(() => {
        if (!service) return 0;
        if (service.type === 'Package') return service.min;
        if (showComments) {
            return comments.split('\n').filter(l => l.trim()).length;
        }
        return parseInt(quantity, 10) || 0;
    }, [service, quantity, comments, showComments]);

    // Memoized calculations
    const { charge, hasDiscount } = useMemo(() => {
        const original = (effectiveQuantity / 1000) * service.rate;
        const discounted = discountPercent > 0 ? original * (1 - discountPercent / 100) : original;
        return {
            charge: discounted,
            hasDiscount: discountPercent > 0,
        };
    }, [effectiveQuantity, service.rate, discountPercent]);

    // Validation with error messages
    const validation = useMemo(() => {
        const errs: FormErrors = {};
        
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
                errs.quantity = `Invalid Quantity: Must be in multiples of 10.`;
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

        const visibleErrs: FormErrors = {};
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

    // Native Back Button Handling
    useEffect(() => {
        try {
            if (typeof showBackButton === 'function') showBackButton();
            let off: (() => void) | undefined;
            if (typeof onBackButtonClick === 'function') {
                off = onBackButtonClick(() => {
                    onClose?.();
                });
            }
            return () => {
                if (off) off();
                if (typeof hideBackButton === 'function') hideBackButton();
            };
        } catch { /* noop */ }
    }, [onClose]);

    // ── Custom Fields helpers ─────────────────────────────────
    const addCustomField = useCallback(() => {
        if (customFields.length >= 10) return;
        setCustomFields(prev => [...prev, { type: 'text', value: '' }]);
    }, [customFields.length]);

    const updateCustomField = useCallback((idx: number, patch: Partial<CustomField>) => {
        setCustomFields(prev => prev.map((f, i) => i === idx ? { ...f, ...patch } : f));
    }, []);

    const removeCustomField = useCallback((idx: number) => {
        setCustomFields(prev => prev.filter((_, i) => i !== idx));
    }, []);


    // Optimistic Mutation Engine
    const { mutate: placeOrderMutation, isPending: submitting } = useMutation({
        mutationFn: api.placeOrder,
        onMutate: async () => {
            await queryClient.cancelQueries({ queryKey: [ 'orders' ] });
            const previousOrders = queryClient.getQueryData<Order[]>([ 'orders' ]) || [];
            const previousBalance = user?.balance || 0;
            const qty = effectiveQuantity;
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
                localStorage.setItem('hasOrdered', 'true');
                setLink(''); setQuantity(''); setComments(''); setAnswerNumber(''); setCustomFields([]); setTouched({});
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
        const qty = effectiveQuantity;
        placeOrderMutation({
            service: service.id,
            link,
            quantity: qty,
            tg_id: user?.id,
            comments: comments || undefined,
            answer_number: answerNumber ? parseInt(answerNumber) : undefined,
            custom_fields: customFields.filter(f => f.value.trim()).length > 0
                ? customFields.filter(f => f.value.trim())
                : undefined,
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
                            subtitle={
                                <>
                                    {service.min} - {service.max.toLocaleString()} • Rate: {formatETB(service.rate)}
                                    <span style={{ fontSize: '10px', opacity: 0.5, marginLeft: '8px' }}>
                                        ({service.original_rate} * {rateMultiplier})
                                    </span>
                                </>
                            }
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
                            header={reqs.labelLink}
                            placeholder={reqs.placeholderLink}
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

                        {showPackage && (
                            <div style={{ padding: '12px 16px', background: 'rgba(0,214,143,0.1)', color: 'var(--color-success)', borderRadius: '8px', fontWeight: 600, fontSize: '14px', margin: '0 16px 12px' }}>
                                📦 Fixed Package Quantity: {service.min}
                            </div>
                        )}

                        {showComments && (
                            <>
                                <Textarea
                                    header={reqs.labelExtra || 'Comments (One per line)'}
                                    placeholder={reqs.placeholderExtra || 'Enter items here (one per line)...'}
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
                                    {comments.split('\n').filter((l: string) => l.trim()).length} / {service.max} lines
                                </div>
                            </>
                        )}

                        {showPoll && (
                            <>
                                <Input
                                    header={reqs.labelExtra || 'Answer Number'}
                                    placeholder={reqs.placeholderExtra || 'e.g. 1, 2'}
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

                    <div style={{ padding: '0 16px', marginTop: '16px' }}>
                        {customFields.length > 0 && (
                            <div className="order-custom-fields-container" style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                                <div style={{ fontSize: '14px', fontWeight: 600, color: 'var(--tg-theme-text-color)' }}>Additional Requirements / Custom Fields</div>
                                {customFields.map((field, idx) => (
                                    <div key={idx} style={{ display: 'flex', gap: '8px', alignItems: 'flex-start', background: 'var(--tg-theme-bg-color, #1a1a2e)', padding: '12px', borderRadius: '12px', border: '1px solid var(--tg-theme-section-separator-color, rgba(255,255,255,0.1))' }}>
                                        <select
                                            value={field.type}
                                            onChange={(e) => updateCustomField(idx, { type: e.target.value as any })}
                                            style={{ background: 'var(--tg-theme-secondary-bg-color, #252542)', color: 'var(--tg-theme-text-color)', border: 'none', padding: '12px 10px', borderRadius: '8px', fontSize: '13px', outline: 'none', cursor: 'pointer', flexShrink: 0 }}
                                        >
                                            {FIELD_TYPES.map(t => (
                                                <option key={t} value={t}>{FIELD_TYPE_LABELS[t]}</option>
                                            ))}
                                        </select>
                                        
                                        {field.type === 'comment' || field.type === 'note' ? (
                                            <textarea
                                                placeholder={`Enter ${field.type}...`}
                                                value={field.value}
                                                onChange={(e) => updateCustomField(idx, { value: e.target.value })}
                                                style={{ flexGrow: 1, minHeight: '60px', background: 'var(--tg-theme-secondary-bg-color, #252542)', color: 'var(--tg-theme-text-color)', border: 'none', padding: '10px 12px', borderRadius: '8px', fontSize: '14px', outline: 'none', resize: 'vertical', fontFamily: 'inherit', width: '100%' }}
                                            />
                                        ) : (
                                            <input
                                                type="text"
                                                placeholder={`Enter ${field.type}...`}
                                                value={field.value}
                                                onChange={(e) => updateCustomField(idx, { value: e.target.value })}
                                                style={{ flexGrow: 1, background: 'var(--tg-theme-secondary-bg-color, #252542)', color: 'var(--tg-theme-text-color)', border: 'none', padding: '12px 12px', borderRadius: '8px', fontSize: '14px', outline: 'none', width: '100%' }}
                                            />
                                        )}

                                        <button
                                            type="button"
                                            onClick={() => removeCustomField(idx)}
                                            style={{ background: 'transparent', border: 'none', color: 'var(--color-danger, #ff4757)', fontSize: '18px', padding: '8px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                                        >
                                            ✕
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}

                        {customFields.length < 10 && (
                            <button
                                type="button"
                                onClick={addCustomField}
                                style={{ background: 'rgba(0, 122, 255, 0.15)', color: '#007aff', border: '1px dashed rgba(0, 122, 255, 0.4)', borderRadius: '12px', padding: '12px', fontWeight: 600, fontSize: '14px', cursor: 'pointer', marginTop: '12px', marginBottom: '16px', transition: 'all 0.2s', width: '100%' }}
                            >
                                + Add Custom Requirement / Comment / Link
                            </button>
                        )}
                    </div>

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
