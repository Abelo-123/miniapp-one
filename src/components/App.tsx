import { AppRoot } from '@telegram-apps/telegram-ui';
import '@telegram-apps/telegram-ui/dist/styles.css';
import { AppProvider, useApp } from '../context/AppContext';
import { BottomNav } from './BottomNav/BottomNav';
import { ToastContainer } from './Toast/Toast';
import { LoadingOverlay } from './LoadingOverlay/LoadingOverlay';
import { OrderPage } from '../pages/OrderPage/OrderPage';
import { HistoryPage } from '../pages/HistoryPage/HistoryPage';
import { DepositPage } from '../pages/DepositPage/DepositPage';
import { MorePage } from '../pages/MorePage/MorePage';
import { useEffect, useRef } from 'react';
import {
  showBackButton,
  hideBackButton,
  onBackButtonClick,
  onSettingsButtonClick,
} from '@telegram-apps/sdk-react';

function AppContent() {
  const { activeTab, setActiveTab, isLoading } = useApp();
  const scrollRef = useRef<HTMLDivElement>(null);

  // Telegram scroll fix: prevent swipe-to-close
  useEffect(() => {
    const el = scrollRef.current;
    if (!el) return;
    const handleTouchStart = () => {
      if (el.scrollTop === 0) {
        el.scrollTop = 1;
      }
    };
    el.addEventListener('touchstart', handleTouchStart, { passive: true });
    return () => el.removeEventListener('touchstart', handleTouchStart);
  }, []);

  // Back button via SDK
  useEffect(() => {
    if (activeTab !== 'order') {
      try { showBackButton(); } catch { /* not available */ }
      const off = onBackButtonClick(() => {
        setActiveTab('order');
      });
      return () => {
        off();
        try { hideBackButton(); } catch { /* ignore */ }
      };
    } else {
      try { hideBackButton(); } catch { /* ignore */ }
    }
  }, [activeTab, setActiveTab]);

  // Settings button via SDK
  useEffect(() => {
    try {
      const off = onSettingsButtonClick(() => {
        setActiveTab('more');
        (window as any).Telegram?.WebApp?.HapticFeedback?.selectionChanged();
      });
      return () => off();
    } catch { /* ignore */ }
  }, [setActiveTab]);

  return (
    <AppRoot>
      <div className="scroll-wrapper" ref={scrollRef}>
        {activeTab === 'order' && <OrderPage />}
        {activeTab === 'history' && <HistoryPage />}
        {activeTab === 'deposit' && <DepositPage />}
        {activeTab === 'more' && <MorePage />}
      </div>
      <BottomNav />
      <ToastContainer />
      <LoadingOverlay visible={isLoading} />
    </AppRoot>
  );
}

export function App() {
  return (
    <AppProvider>
      <AppContent />
    </AppProvider>
  );
}
