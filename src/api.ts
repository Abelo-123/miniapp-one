import type {
    Service, Deposit, ChatMessage,
    AuthResponse, OrderResponse, OrdersListResponse,
    DepositResponse, AlertsResponse, StatusSyncResponse,
} from './types';
import { API_BASE_URL } from './constants';

const NODE_API_URL = import.meta.env.VITE_NODE_API_URL || '/api';

const isDev = import.meta.env.DEV;

function debug(...args: any[]) {
    if (isDev) console.log(...args);
}

function debugError(...args: any[]) {
    if (isDev) console.error(...args);
}

async function apiFetch<T>(
    endpoint: string,
    options?: RequestInit
): Promise<T> {
    const url = `${API_BASE_URL}${endpoint}`;
    debug('[API] Fetching:', url);
    
    // Create abort controller for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
    
    try {
        const res = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...(options?.headers || {}),
            },
            ...options,
            signal: controller.signal,
        });
        
        clearTimeout(timeoutId);
        
        if (!res.ok) {
            const body = await res.text();
            debugError('[API] Error:', res.status, body);
            throw new Error(body || `HTTP ${res.status}`);
        }
        const data = await res.json();
        debug('[API] Success:', endpoint);
        return data;
    } catch (err: any) {
        clearTimeout(timeoutId);
        if (err.name === 'AbortError') {
            throw new Error('Request timeout - please check your connection');
        }
        throw err;
    }
}

async function nodeApiFetch<T>(
    endpoint: string,
    options?: RequestInit
): Promise<T> {
    const url = `${NODE_API_URL}${endpoint}`;
    debug('[Node API] Fetching:', url);
    const res = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            ...(options?.headers || {}),
        },
        ...options,
    });
    if (!res.ok) {
        const body = await res.text();
        debugError('[Node API] Error:', res.status, body);
        throw new Error(body || `HTTP ${res.status}`);
    }
    const data = await res.json();
    debug('[Node API] Success:', endpoint);
    return data;
}

export async function authenticateTelegram(initData: string): Promise<AuthResponse> {
    return apiFetch<AuthResponse>('/telegram_auth.php', {
        method: 'POST',
        body: JSON.stringify({ initData }),
    });
}

const SERVICES_CACHE_KEY = 'paxyo_services_cache';
const SERVICES_TIMESTAMP_KEY = 'paxyo_services_timestamp';
const SETTINGS_CACHE_KEY = 'paxyo_settings_cache';
const SETTINGS_TIMESTAMP_KEY = 'paxyo_settings_timestamp';
const CACHE_DURATION = 5 * 60 * 1000;
const SETTINGS_CACHE_DURATION = 15 * 60 * 1000;

export async function getServices(useCache = true): Promise<Service[]> {
    if (useCache) {
        const cached = localStorage.getItem(SERVICES_CACHE_KEY);
        const timestamp = localStorage.getItem(SERVICES_TIMESTAMP_KEY);
        if (cached && timestamp) {
            const age = Date.now() - parseInt(timestamp);
            if (age < CACHE_DURATION) {
                debug('[Services] Using cached data');
                return JSON.parse(cached);
            }
            // Even if cache is old, use it if API fails later
        }
    }

    try {
        debug('[Services] Fetching from API...');
        const data = await apiFetch<Service[]>('/get_service.php');
        localStorage.setItem(SERVICES_CACHE_KEY, JSON.stringify(data));
        localStorage.setItem(SERVICES_TIMESTAMP_KEY, Date.now().toString());
        debug('[Services] Got', data.length, 'services');
        return data;
    } catch (err) {
        debugError('[Services] Error:', err);
        // Try to use ANY cached data, even if old
        const cached = localStorage.getItem(SERVICES_CACHE_KEY);
        if (cached) {
            debug('[Services] Falling back to cache (may be stale)');
            return JSON.parse(cached);
        }
        // Return empty array instead of throwing - prevents app crash
        debug('[Services] No cache available, returning empty');
        return [];
    }
}

export async function refreshServices(): Promise<Service[]> {
    const data = await apiFetch<Service[]>('/get_service.php?refresh=1');
    localStorage.setItem(SERVICES_CACHE_KEY, JSON.stringify(data));
    localStorage.setItem(SERVICES_TIMESTAMP_KEY, Date.now().toString());
    return data;
}

export async function getRecommended(): Promise<number[]> {
    return apiFetch<number[]>('/get_recommended.php');
}

export interface PlaceOrderPayload {
    service: number;
    link: string;
    quantity: number;
    tg_id?: number;
    comments?: string;
    answer_number?: number;
}

export async function placeOrder(payload: PlaceOrderPayload): Promise<OrderResponse> {
    return apiFetch<OrderResponse>('/process_order.php', {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function getOrders(): Promise<OrdersListResponse> {
    return apiFetch<OrdersListResponse>('/get_orders.php');
}

export async function checkOrderStatus(): Promise<StatusSyncResponse> {
    return apiFetch<StatusSyncResponse>('/check_order_status.php');
}

export async function requestRefill(orderId: number): Promise<{ success: boolean; message: string }> {
    return apiFetch('/user_actions.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'refill', order_id: orderId }),
    });
}

export async function processDeposit(amount: number, referenceId: string): Promise<DepositResponse> {
    return nodeApiFetch<DepositResponse>('/deposit', {
        method: 'POST',
        body: JSON.stringify({ amount, tx_ref: referenceId }),
    });
}

export async function getDeposits(initData: string): Promise<Deposit[]> {
    return nodeApiFetch<Deposit[]>('/deposits', {
        method: 'POST',
        body: JSON.stringify({ initData })
    });
}

export async function getBalance(initData: string): Promise<{ success: boolean; balance: number }> {
    return nodeApiFetch<{ success: boolean; balance: number }>('/balance', {
        method: 'POST',
        body: JSON.stringify({ initData })
    });
}

export async function getAlerts(): Promise<AlertsResponse> {
    return apiFetch<AlertsResponse>('/get_alerts.php');
}

export async function markAlertsRead(): Promise<{ success: boolean }> {
    return apiFetch('/mark_alerts_read.php');
}

export async function sendChat(message: string): Promise<{ success: boolean }> {
    return apiFetch('/chat_api.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'send', message }),
    });
}

export async function fetchChat(): Promise<{ success: boolean; messages: ChatMessage[] }> {
    return apiFetch('/chat_api.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'fetch' }),
    });
}

export async function heartbeat(): Promise<{ ok: number }> {
    return apiFetch('/heartbeat.php');
}

// ---------------------------------------------------------------------------
// Init Data Logging
// ---------------------------------------------------------------------------
// Log the Telegram initData payload for new users when they enter the app.
// This helps establish a trace of user onboarding (username/profile, etc).
export async function logInitData(initData: string): Promise<{ success: boolean }> {
    return apiFetch<{ success: boolean }>('/log_init_data.php', {
        method: 'POST',
        body: JSON.stringify({ initData }),
    });
}

export interface AppSettings {
    rateMultiplier: number;
    discountPercent: number;
    holidayName: string;
    maintenanceMode: boolean;
    userCanOrder: boolean;
    marqueeText: string;
}

export async function getSettings(useCache = true): Promise<AppSettings> {
    if (useCache) {
        const cached = localStorage.getItem(SETTINGS_CACHE_KEY);
        const timestamp = localStorage.getItem(SETTINGS_TIMESTAMP_KEY);
        if (cached && timestamp) {
            const age = Date.now() - parseInt(timestamp);
            if (age < SETTINGS_CACHE_DURATION) {
                debug('[Settings] Using cached data');
                return JSON.parse(cached);
            }
        }
    }

    try {
        const data = await apiFetch<AppSettings>('/get_settings.php');
        localStorage.setItem(SETTINGS_CACHE_KEY, JSON.stringify(data));
        localStorage.setItem(SETTINGS_TIMESTAMP_KEY, Date.now().toString());
        return data;
    } catch (err) {
        debugError('[Settings] Error:', err);
        const cached = localStorage.getItem(SETTINGS_CACHE_KEY);
        if (cached) {
            debug('[Settings] Falling back to cache (may be stale)');
            return JSON.parse(cached);
        }
        // Return default settings instead of throwing
        debug('[Settings] No cache available, returning defaults');
        return {
            rateMultiplier: 1,
            discountPercent: 0,
            holidayName: '',
            maintenanceMode: false,
            userCanOrder: true,
            marqueeText: 'Welcome to Paxyo SMM!',
        };
    }
}
