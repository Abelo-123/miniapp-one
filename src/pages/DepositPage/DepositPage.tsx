import { useState, useMemo } from 'react';
import { List, Section, Cell, Button, Banner } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';
import { formatETB } from '../../constants';
import { hapticSelection, hapticImpact, hapticNotification, getInitDataString } from '../../helpers/telegram';

const PRESET_AMOUNTS = [10, 100, 1000, 10000];

export function DepositPage() {
    const { user, deposits, setBalance, setDeposits, showToast } = useApp();
    const [amount, setAmount] = useState('');
    const [depositing, setDepositing] = useState(false);

    const balance = user?.balance ?? 0;

    const handleDeposit = async () => {
        const val = parseFloat(amount);
        if (!val || val < 10) {
            showToast('error', 'Minimum deposit is 10 ETB');
            hapticNotification('error');
            return;
        }

        setDepositing(true);
        try {
            const initData = await getInitDataString();
            
            const response = await fetch(`${import.meta.env.VITE_API_URL}/deposit_handler.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: val,
                    initData: initData,
                }),
            });

            const data = await response.json();

            if (data.success) {
                if (user) setBalance(data.new_balance);

                const newDeposit = {
                    id: Date.now(),
                    amount: val,
                    reference_id: data.reference_id || `chapa-${Date.now()}`,
                    status: 'completed' as const,
                    method: 'Chapa',
                    created_at: new Date().toISOString(),
                };

                setDeposits([newDeposit, ...deposits]);
                showToast('success', `Deposited ${formatETB(val)} successfully!`);
                hapticImpact('heavy');
                hapticNotification('success');
                setAmount('');
            } else {
                showToast('error', data.error || 'Payment failed');
                hapticNotification('error');
            }
        } catch (err) {
            console.error('Deposit error:', err);
            showToast('error', 'Payment failed. Please try again.');
            hapticNotification('error');
        } finally {
            setDepositing(false);
        }
    };

    const recentDeposits = useMemo(() => deposits.slice(0, 5), [deposits]);

    return (
        <List>
            <Section>
                <Banner
                    header="Current Balance"
                    description="Your available funds"
                >
                    <div style={{ 
                        fontSize: '2.5em', 
                        fontWeight: 'bold', 
                        color: 'var(--tg-theme-link-color)',
                        fontFamily: 'monospace',
                    }}>
                        {formatETB(balance)}
                    </div>
                </Banner>
            </Section>

            <Section header="Deposit Funds">
                <div style={{ padding: '0 16px 8px' }}>
                    <div style={{ 
                        display: 'flex', 
                        alignItems: 'center',
                        background: 'var(--tg-theme-secondary-bg-color)',
                        borderRadius: '12px',
                        border: '1px solid var(--tg-theme-hint-color, #333)',
                        overflow: 'hidden',
                    }}>
                        <span style={{ 
                            padding: '16px 12px', 
                            color: 'var(--tg-theme-hint-color)',
                            fontWeight: 600,
                            borderRight: '1px solid var(--tg-theme-hint-color, #333)',
                        }}>ETB</span>
                        <input
                            type="number"
                            inputMode="decimal"
                            pattern="[0-9]*"
                            placeholder="0"
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            style={{
                                flex: 1,
                                background: 'transparent',
                                border: 'none',
                                outline: 'none',
                                padding: '16px',
                                fontSize: '1.25em',
                                fontWeight: 600,
                                color: 'var(--tg-theme-text-color)',
                                width: '100%',
                            }}
                        />
                    </div>
                </div>

                <div style={{ 
                    padding: '8px 16px 16px', 
                    display: 'grid', 
                    gridTemplateColumns: 'repeat(4, 1fr)', 
                    gap: '8px' 
                }}>
                    {PRESET_AMOUNTS.map(amt => (
                        <Button
                            key={amt}
                            size="s"
                            mode={amount === String(amt) ? 'filled' : 'bezeled'}
                            onClick={() => {
                                hapticSelection();
                                setAmount(String(amt));
                            }}
                            style={{
                                fontWeight: 600,
                            }}
                        >
                            +{amt >= 1000 ? `${amt/1000}k` : amt}
                        </Button>
                    ))}
                </div>

                <div style={{ padding: '0 16px 16px' }}>
                    <Button
                        size="l"
                        stretched
                        onClick={handleDeposit}
                        loading={depositing}
                        disabled={!amount || parseFloat(amount) < 10}
                    >
                        Deposit with Chapa
                    </Button>
                </div>
            </Section>

            <Section header="Recent Deposits">
                {recentDeposits.length === 0 ? (
                    <Cell disabled>No recent deposits</Cell>
                ) : (
                    recentDeposits.map(d => (
                        <Cell
                            key={d.id}
                            after={
                                <span style={{ 
                                    color: 'var(--tg-theme-link-color)',
                                    fontWeight: 600,
                                }}>
                                    +{formatETB(d.amount)}
                                </span>
                            }
                            description={new Date(d.created_at).toLocaleDateString() + ' • ' + d.method}
                        >
                            <span style={{
                                color: d.status === 'completed' ? 'var(--tg-theme-link-color)' : 'var(--tg-theme-destructive-text-color)',
                            }}>
                                {d.status.charAt(0).toUpperCase() + d.status.slice(1)}
                            </span>
                        </Cell>
                    ))
                )}
            </Section>
        </List>
    );
}
