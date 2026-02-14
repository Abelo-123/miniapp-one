import { Tabbar } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';
import type { TabId } from '../../types';
import { hapticSelection } from '../../helpers/telegram';

const TABS: { id: TabId; label: string; icon: string }[] = [
    { id: 'order', label: 'Order', icon: '🛒' },
    { id: 'history', label: 'History', icon: '📋' },
    { id: 'deposit', label: 'Deposit', icon: '💰' },
    { id: 'more', label: 'More', icon: '⚙️' },
];

export function BottomNav() {
    const { activeTab, setActiveTab, unreadAlerts } = useApp();

    const handleTabClick = (tab: TabId) => {
        setActiveTab(tab);
        hapticSelection();
    };

    return (
        <Tabbar>
            {TABS.map(tab => (
                <Tabbar.Item
                    key={tab.id}
                    selected={activeTab === tab.id}
                    text={tab.id === 'more' && unreadAlerts > 0
                        ? `${tab.label} (${unreadAlerts > 9 ? '9+' : unreadAlerts})`
                        : tab.label
                    }
                    onClick={() => handleTabClick(tab.id)}
                >
                    <span style={{ fontSize: 24 }}>{tab.icon}</span>
                </Tabbar.Item>
            ))}
        </Tabbar>
    );
}
