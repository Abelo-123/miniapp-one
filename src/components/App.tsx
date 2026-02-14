import { AppRoot } from '@telegram-apps/telegram-ui';
// telegram-ui styles already imported in index.tsx — no duplicate import
import { AppProvider, useApp } from '../context/AppContext';
import { BottomNav } from './BottomNav/BottomNav';
import { ToastContainer } from './Toast/Toast';
import { LoadingOverlay } from './LoadingOverlay/LoadingOverlay';
import { lazy, Suspense, useEffect, useRef } from 'react';
import {
  showBackButton,
  hideBackButton,
  onBackButtonClick,
  onSettingsButtonClick,
} from '@telegram-apps/sdk-react';
import { hapticSelection } from '../helpers/telegram';

// ─── Lazy-loaded pages (code-split per tab) ─────────────────
const OrderPage = lazy(() => import('../pages/OrderPage/OrderPage').then(m => ({ default: m.OrderPage })));
const HistoryPage = lazy(() => import('../pages/HistoryPage/HistoryPage').then(m => ({ default: m.HistoryPage })));
const DepositPage = lazy(() => import('../pages/DepositPage/DepositPage').then(m => ({ default: m.DepositPage })));
const MorePage = lazy(() => import('../pages/MorePage/MorePage').then(m => ({ default: m.MorePage })));

// Minimal fallback to avoid layout shift while lazy chunk loads
const TabFallback = () => (
  <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}>
    <div style={{
      width: 20, height: 20,
      border: '2px solid #222', borderTopColor: '#3498db',
      borderRadius: '50%', animation: 'spin .6s linear infinite',
    }} />
  </div>
);

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
        hapticSelection();
      });
      return () => off();
    } catch { /* ignore */ }
  }, [setActiveTab]);

  return (
    <AppRoot>
      <div className="scroll-wrapper" ref={scrollRef}>
        <Suspense fallback={<TabFallback />}>
          {activeTab === 'order' && <OrderPage />}
          {activeTab === 'history' && <HistoryPage />}
          {activeTab === 'deposit' && <DepositPage />}
          {activeTab === 'more' && <MorePage />}
        </Suspense>
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
