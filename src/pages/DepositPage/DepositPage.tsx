import { useState } from 'react';
import { List, Section, Cell, Input, Button, Banner } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';
import { formatETB } from '../../constants';
import { hapticSelection, hapticImpact, hapticNotification } from '../../helpers/telegram';

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
                id: Date.now(),
                amount: val,
                reference_id: `chapa-${Date.now()}`,
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
                <Banner
                    header="Current Balance"
                    description={user ? formatETB(user.balance) : 'Loading...'}
                    background="var(--tg-theme-secondary-bg-color)"
                >
                    <div style={{ fontSize: '2em', fontWeight: 'bold', color: 'var(--tg-theme-text-color)' }}>
                        {user ? formatETB(user.balance) : '...'}
                    </div>
                </Banner>
            </Section>

            <Section header="Deposit Funds">
                <Input
                    header="Amount (ETB)"
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

                <div style={{ padding: '0 16px 16px' }}>
                    <Button
                        size="l"
                        stretched
                        onClick={handleDeposit}
                        loading={depositing}
                        disabled={!amount || parseInt(amount) < 50}
                    >
                        Deposit with Chapa
                    </Button>
                </div>
            </Section>

            <Section header="Recent Deposits">
                {deposits.length === 0 ? (
                    <Cell disabled>No recent deposits</Cell>
                ) : (
                    deposits.map(d => (
                        <Cell
                            key={d.id}
                            after={
                                <span style={{ color: 'var(--tg-theme-link-color)' }}>
                                    +{formatETB(d.amount)}
                                </span>
                            }
                            description={new Date(d.created_at).toLocaleDateString()}
                            subtitle={d.status}
                        >
                            {d.method}
                        </Cell>
                    ))
                )}
            </Section>
        </List>
    );
}
