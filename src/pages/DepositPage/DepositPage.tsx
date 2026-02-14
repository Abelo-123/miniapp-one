import { useState } from 'react';
import { List, Section, Button, Input, Banner, Placeholder, Cell } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';
import { formatETB } from '../../constants';
import { hapticSelection, hapticImpact, hapticNotification } from '../../helpers/telegram';
import './DepositPage.css';

const PRESET_AMOUNTS = [100, 500, 1000, 2000];

export function DepositPage() {
    const { user, deposits, setBalance, setDeposits, showToast } = useApp();
    const [amount, setAmount] = useState('');
    const [depositing, setDepositing] = useState(false);

    const handleDeposit = async () => {
        const val = parseInt(amount);
        if (!val || val < 50) {
            showToast('error', 'Minimum deposit is 50 ETB');
            hapticNotification('error');
            return;
        }

        setDepositing(true);
        try {
            // Mock Chapa payment
            await new Promise(r => setTimeout(r, 1500));

            // Success
            if (user) setBalance(user.balance + val);

            const newDeposit = {
                id: Math.floor(Math.random() * 90000) + 10000,
                amount: val,
                reference_id: `CP_${Math.random().toString(36).substr(2, 9).toUpperCase()}`,
                status: 'completed' as const,
                method: 'Chapa',
                created_at: new Date().toISOString(),
            };

            setDeposits([newDeposit, ...deposits]);
            showToast('success', `Deposited ${formatETB(val)} successfully!`);

            hapticImpact('heavy');
            hapticNotification('success');

            setAmount('');
        } catch {
            showToast('error', 'Payment failed');
            hapticNotification('error');
        } finally {
            setDepositing(false);
        }
    };

    return (
        <List>
            <Section>
                <div style={{ padding: '16px' }}>
                    <div className="dp-balance">
                        <span style={{ color: 'var(--tg-theme-hint-color)', fontSize: '13px' }}>Current Balance</span>
                        <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                            {user ? formatETB(user.balance) : 'Loading...'}
                        </div>
                    </div>
                </div>
            </Section>

            <Section header="Add Funds">
                <Input
                    header="Amount"
                    placeholder="Enter amount (min 50)"
                    type="number"
                    value={amount}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setAmount(e.target.value)}
                />

                <div style={{ padding: '12px 16px', display: 'flex', gap: 8, overflowX: 'auto' }}>
                    {PRESET_AMOUNTS.map(amt => (
                        <Button
                            key={amt}
                            size="s"
                            mode="bezeled"
                            onClick={() => {
                                hapticSelection();
                                setAmount(String(amt));
                            }}
                        >
                            +{amt}
                        </Button>
                    ))}
                </div>

                <div style={{ padding: '16px' }}>
                    <Button
                        size="l"
                        stretched
                        onClick={handleDeposit}
                        loading={depositing}
                        disabled={!amount || parseInt(amount) < 50}
                    >
                        Deposit via Chapa
                    </Button>
                </div>
            </Section>

            {/* ── Deposit History Table ── */}
            <div className="dp-table-container">
                <div className="dp-table-header">
                    <div className="dp-col dp-col-id">ORDER ID</div>
                    <div className="dp-col dp-col-amount">AMOUNT</div>
                    <div className="dp-col dp-col-ref">TRANSACTION</div>
                    <div className="dp-col dp-col-date">DATE</div>
                </div>

                {deposits.length === 0 ? (
                    <Placeholder description="No deposits yet" />
                ) : (
                    deposits.map(d => (
                        <div key={d.id} className="dp-table-row">
                            <div className="dp-col dp-col-id">#{d.id}</div>
                            <div className="dp-col dp-col-amount">{d.amount}</div>
                            <div className="dp-col dp-col-ref">{d.reference_id}</div>
                            <div className="dp-col dp-col-date">
                                {new Date(d.created_at).toLocaleDateString(undefined, {
                                    month: 'numeric', day: 'numeric', year: '2-digit'
                                })}
                            </div>
                        </div>
                    ))
                )}
            </div>
        </List>
    );
}
