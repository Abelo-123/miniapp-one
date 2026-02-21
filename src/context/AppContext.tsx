import { createContext, useContext, useState, useCallback, useEffect, type ReactNode } from 'react';
import type { UserProfile, Service, Order, Deposit, Alert, ChatMessage, TabId, ToastMessage, SocialPlatform } from '../types';
import { TOAST_DURATION } from '../constants';
import {
    isTelegramEnv,
    hapticSelection,
    cloudSet,
    cloudGet,
    getInitDataUser,
} from '../helpers/telegram';
import * as api from '../api';

interface AppState {
    user: UserProfile | null;
    isTelegramApp: boolean;
    services: Service[];
    recommendedIds: number[];
    selectedPlatform: SocialPlatform | null;
    selectedCategory: string | null;
    selectedService: Service | null;
    orders: Order[];
    deposits: Deposit[];
    alerts: Alert[];
    chatMessages: ChatMessage[];
    rateMultiplier: number;
    discountPercent: number;
    holidayName: string;
    maintenanceMode: boolean;
    userCanOrder: boolean;
    marqueeText: string;
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
    refreshServices: () => Promise<void>;
    refreshOrders: () => Promise<void>;
    refreshDeposits: () => Promise<void>;
    refreshAlerts: () => Promise<void>;
}

type AppContextType = AppState & AppActions;

const AppContext = createContext<AppContextType | null>(null);

export function useApp(): AppContextType {
    const ctx = useContext(AppContext);
    if (!ctx) throw new Error('useApp must be used within AppProvider');
    return ctx;
}

export function AppProvider({ children }: { children: ReactNode }) {
    const isTelegramApp = isTelegramEnv();

    const [user, setUser] = useState<UserProfile | null>(null);
    const [services, setServices] = useState<Service[]>([]);
    const [recommendedIds, setRecommendedIds] = useState<number[]>([]);
    const [selectedPlatform, setSelectedPlatform] = useState<SocialPlatform | null>(null);
    const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
    const [selectedService, setSelectedService] = useState<Service | null>(null);
    const [orders, setOrders] = useState<Order[]>([]);
    const [deposits, setDeposits] = useState<Deposit[]>([]);
    const [alerts, setAlerts] = useState<Alert[]>([]);
    const [chatMessages, setChatMessages] = useState<ChatMessage[]>([]);
    const [activeTab, setActiveTab] = useState<TabId>('order');
    const [toasts, setToasts] = useState<ToastMessage[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [unreadAlerts, setUnreadAlerts] = useState(0);
    const [settings, setSettings] = useState({
        rateMultiplier: 1,
        discountPercent: 0,
        holidayName: '',
        maintenanceMode: false,
        userCanOrder: true,
        marqueeText: 'Welcome to Paxyo SMM!',
    });

    const refreshServices = useCallback(async () => {
        try {
            const data = await api.getServices(false);
            const transformed: Service[] = data.map((s: any) => ({
                id: s.service,
                category: s.category,
                name: s.name,
                type: s.type as Service['type'],
                rate: parseFloat(s.rate),
                min: s.min,
                max: s.max,
                averageTime: s.average_time || s.averageTime || '',
                refill: s.refill,
                cancel: s.cancel,
            }));
            setServices(transformed);
            api.refreshServices().catch(console.error);
        } catch (err) {
            console.error('Failed to fetch services:', err);
        }
    }, []);

    const refreshOrders = useCallback(async () => {
        // Temporarily disabled
    }, []);

    const refreshDeposits = useCallback(async () => {
        // Temporarily disabled
    }, []);

    const refreshAlerts = useCallback(async () => {
        // Temporarily disabled
    }, []);

    useEffect(() => {
        const loadData = async () => {
            await refreshServices();
        };
        loadData();
    }, []);

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

    useEffect(() => {
        setSelectedCategory(null);
        setSelectedService(null);
    }, [selectedPlatform]);

    useEffect(() => {
        setSelectedService(null);
    }, [selectedCategory]);

    useEffect(() => {
        try {
            const tgUser = getInitDataUser();
            if (tgUser) {
                setUser({
                    id: tgUser.id,
                    first_name: tgUser.first_name,
                    last_name: tgUser.last_name,
                    username: tgUser.username,
                    display_name: [tgUser.first_name, tgUser.last_name].filter(Boolean).join(' '),
                    photo_url: tgUser.photo_url ?? '',
                    balance: 0,
                });
            }
        } catch (e) {}
    }, []);

    const handleSetActiveTab = useCallback((tab: TabId) => {
        setActiveTab(tab);
        if (isTelegramApp) {
            hapticSelection();
            void cloudSet('last_tab', tab);
        }
    }, [isTelegramApp]);

    const handleSetSelectedPlatform = useCallback((p: SocialPlatform | null) => {
        setSelectedPlatform(p);
        if (p && isTelegramApp) hapticSelection();
    }, [isTelegramApp]);

    const handleSetSelectedService = useCallback((s: Service | null) => {
        setSelectedService(s);
        if (s && isTelegramApp) hapticSelection();
    }, [isTelegramApp]);

    useEffect(() => {
        if (!isTelegramApp) return;
        void (async () => {
            const val = await cloudGet('last_tab');
            if (val && ['order', 'history', 'deposit', 'more'].includes(val)) {
                setActiveTab(val as TabId);
            }
        })();
    }, [isTelegramApp]);

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
        rateMultiplier: settings.rateMultiplier,
        discountPercent: settings.discountPercent,
        holidayName: settings.holidayName,
        maintenanceMode: settings.maintenanceMode,
        userCanOrder: settings.userCanOrder,
        marqueeText: settings.marqueeText,
        activeTab,
        toasts,
        isLoading,
        unreadAlerts,
        setUser,
        setActiveTab: handleSetActiveTab,
        setSelectedPlatform: handleSetSelectedPlatform,
        setSelectedCategory,
        setSelectedService: handleSetSelectedService,
        setOrders,
        setDeposits,
        setAlerts,
        setChatMessages,
        setBalance,
        setIsLoading,
        setUnreadAlerts,
        showToast,
        removeToast,
        refreshServices,
        refreshOrders,
        refreshDeposits,
        refreshAlerts,
    };

    return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
}
