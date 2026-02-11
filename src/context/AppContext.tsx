import { createContext, useContext, useState, useCallback, useEffect, type ReactNode } from 'react';
import type { UserProfile, Service, Order, Deposit, Alert, ChatMessage, TabId, ToastMessage, SocialPlatform } from '../types';
import { MOCK_USER, MOCK_SERVICES, MOCK_RECOMMENDED, MOCK_ORDERS, MOCK_DEPOSITS, MOCK_ALERTS, MOCK_CHAT, MOCK_SETTINGS } from '../mocks/data';
import { TOAST_DURATION } from '../constants';

interface AppState {
    // Auth
    user: UserProfile | null;
    isTelegramApp: boolean;

    // Services
    services: Service[];
    recommendedIds: number[];

    // Order flow
    selectedPlatform: SocialPlatform | null;
    selectedCategory: string | null;
    selectedService: Service | null;

    // Data
    orders: Order[];
    deposits: Deposit[];
    alerts: Alert[];
    chatMessages: ChatMessage[];

    // Settings
    rateMultiplier: number;
    discountPercent: number;
    holidayName: string;
    maintenanceMode: boolean;
    userCanOrder: boolean;
    marqueeText: string;

    // UI state
    activeTab: TabId;
    toasts: ToastMessage[];
    isLoading: boolean;
    unreadAlerts: number;
}

interface AppActions {
    setUser: (user: UserProfile | null) => void;
    setActiveTab: (tab: TabId) => void;
    setSelectedPlatform: (p: SocialPlatform | null) => void;
    setSelectedCategory: (c: string | null) => void;
    setSelectedService: (s: Service | null) => void;
    setOrders: (orders: Order[]) => void;
    setDeposits: (deposits: Deposit[]) => void;
    setAlerts: (alerts: Alert[]) => void;
    setChatMessages: (msgs: ChatMessage[]) => void;
    setBalance: (balance: number) => void;
    setIsLoading: (loading: boolean) => void;
    setUnreadAlerts: (count: number) => void;
    showToast: (type: ToastMessage['type'], message: string) => void;
    removeToast: (id: string) => void;
}

type AppContextType = AppState & AppActions;

const AppContext = createContext<AppContextType | null>(null);

export function useApp(): AppContextType {
    const ctx = useContext(AppContext);
    if (!ctx) throw new Error('useApp must be used within AppProvider');
    return ctx;
}

export function AppProvider({ children }: { children: ReactNode }) {
    const isTelegramApp = !!(window as any).Telegram?.WebApp;

    const [user, setUser] = useState<UserProfile | null>(MOCK_USER);
    const [services] = useState<Service[]>(MOCK_SERVICES);
    const [recommendedIds] = useState<number[]>(MOCK_RECOMMENDED);
    const [selectedPlatform, setSelectedPlatform] = useState<SocialPlatform | null>(null);
    const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
    const [selectedService, setSelectedService] = useState<Service | null>(null);
    const [orders, setOrders] = useState<Order[]>(MOCK_ORDERS);
    const [deposits, setDeposits] = useState<Deposit[]>(MOCK_DEPOSITS);
    const [alerts, setAlerts] = useState<Alert[]>(MOCK_ALERTS);
    const [chatMessages, setChatMessages] = useState<ChatMessage[]>(MOCK_CHAT);
    const [activeTab, setActiveTab] = useState<TabId>('order');
    const [toasts, setToasts] = useState<ToastMessage[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [unreadAlerts, setUnreadAlerts] = useState(MOCK_ALERTS.filter(a => !a.is_read).length);

    const setBalance = useCallback((balance: number) => {
        setUser(prev => prev ? { ...prev, balance } : prev);
    }, []);

    const showToast = useCallback((type: ToastMessage['type'], message: string) => {
        const id = Date.now().toString() + Math.random().toString(36).slice(2);
        setToasts(prev => [...prev, { id, type, message }]);
        setTimeout(() => {
            setToasts(prev => prev.filter(t => t.id !== id));
        }, TOAST_DURATION);
    }, []);

    const removeToast = useCallback((id: string) => {
        setToasts(prev => prev.filter(t => t.id !== id));
    }, []);

    // Reset category/service when platform changes
    useEffect(() => {
        setSelectedCategory(null);
        setSelectedService(null);
    }, [selectedPlatform]);

    // Reset service when category changes
    useEffect(() => {
        setSelectedService(null);
    }, [selectedCategory]);

    const value: AppContextType = {
        user,
        isTelegramApp,
        services,
        recommendedIds,
        selectedPlatform,
        selectedCategory,
        selectedService,
        orders,
        deposits,
        alerts,
        chatMessages,
        rateMultiplier: MOCK_SETTINGS.rateMultiplier,
        discountPercent: MOCK_SETTINGS.discountPercent,
        holidayName: MOCK_SETTINGS.holidayName,
        maintenanceMode: MOCK_SETTINGS.maintenanceMode,
        userCanOrder: !MOCK_SETTINGS.maintenanceMode,
        marqueeText: MOCK_SETTINGS.marqueeText,
        activeTab,
        toasts,
        isLoading,
        unreadAlerts,
        setUser,
        setActiveTab,
        setSelectedPlatform,
        setSelectedCategory,
        setSelectedService,
        setOrders,
        setDeposits,
        setAlerts,
        setChatMessages,
        setBalance,
        setIsLoading,
        setUnreadAlerts,
        showToast,
        removeToast,
    };

    return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
}
