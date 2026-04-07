import { useApp } from '../../context/AppContext';
import { formatETB } from '../../constants';

import { Avatar, Button } from '@telegram-apps/telegram-ui';

interface Props {
    onSearchClick: () => void;
    onNotificationClick: () => void;
    onChatClick?: () => void;
}

export function GlobalHeader({ onSearchClick, onNotificationClick, onChatClick }: Props) {
    const { user, unreadAlerts } = useApp();

    return (
        <div className="global-header">
            <div className="global-header__left">
                <Avatar 
                    size={48} 
                    src={user?.photo_url} 
                    acronym={user?.first_name ? user.first_name[0] : 'U'} 
                    style={{ border: '2px solid rgba(124,92,252,0.4)' }}
                />
                <div className="global-header__info">
                    <div className="global-header__name">
                        {user?.first_name || 'User'} 🕊
                    </div>
                    <div className="global-header__balance">
                        {user ? formatETB(user.balance) : '0.00 ETB'}
                    </div>
                </div>
            </div>
            <div className="global-header__actions">
                {onChatClick && (
                    <Button
                        mode="plain"
                        className="global-header__action-btn"
                        onClick={onChatClick}
                        style={{ padding: 8 }}
                    >
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                    </Button>
                )}
                <Button
                    mode="plain"
                    className="global-header__action-btn"
                    onClick={onNotificationClick}
                    style={{ padding: 8 }}
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                    {unreadAlerts > 0 && (
                        <span className="global-header__badge">
                            {unreadAlerts > 9 ? '9+' : unreadAlerts}
                        </span>
                    )}
                </Button>
                <Button
                    mode="plain"
                    className="global-header__action-btn global-header__action-btn--search"
                    onClick={onSearchClick}
                    style={{ padding: 8, color: 'var(--accent-secondary)' }}
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </Button>
            </div>
        </div>
    );
}
