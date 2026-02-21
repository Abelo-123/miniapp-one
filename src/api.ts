import type {
    Service, Deposit, ChatMessage,
    AuthResponse, OrderResponse, OrdersListResponse,
    DepositResponse, AlertsResponse, StatusSyncResponse,
} from './types';
import { API_BASE_URL } from './constants';

// ─── Base Fetch Helper ────────────────────────────────────────
async function apiFetch<T>(
    endpoint: string,
    options?: RequestInit
): Promise<T> {
    const url = `${API_BASE_URL}${endpoint}`;
    const res = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            ...(options?.headers || {}),
        },
        ...options,
    });
    if (!res.ok) {
        const body = await res.text();
        throw new Error(body || `HTTP ${res.status}`);
    }
    return res.json();
}

// ─── Auth ─────────────────────────────────────────────────────
export async function authenticateTelegram(initData: string): Promise<AuthResponse> {
    return apiFetch<AuthResponse>('/telegram_auth.php', {
        method: 'POST',
        body: JSON.stringify({ initData }),
    });
}

// ─── Services ─────────────────────────────────────────────────
const SERVICES_CACHE_KEY = 'paxyo_services_cache';
const SERVICES_TIMESTAMP_KEY = 'paxyo_services_timestamp';
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

export async function getServices(useCache = true): Promise<Service[]> {
    if (useCache) {
        const cached = localStorage.getItem(SERVICES_CACHE_KEY);
        const timestamp = localStorage.getItem(SERVICES_TIMESTAMP_KEY);
        if (cached && timestamp) {
            const age = Date.now() - parseInt(timestamp);
            if (age < CACHE_DURATION) {
                return JSON.parse(cached);
            }
        }
    }
    
    try {
        const data = await apiFetch<Service[]>('/get_services.php');
        localStorage.setItem(SERVICES_CACHE_KEY, JSON.stringify(data));
        localStorage.setItem(SERVICES_TIMESTAMP_KEY, Date.now().toString());
        return data;
    } catch (err) {
        const cached = localStorage.getItem(SERVICES_CACHE_KEY);
        if (cached) {
            return JSON.parse(cached);
        }
        throw err;
    }
}

export async function refreshServices(): Promise<Service[]> {
    const data = await apiFetch<Service[]>('/get_services.php?refresh=1');
    localStorage.setItem(SERVICES_CACHE_KEY, JSON.stringify(data));
    localStorage.setItem(SERVICES_TIMESTAMP_KEY, Date.now().toString());
    return data;
}

export async function getRecommended(): Promise<number[]> {
    return apiFetch<number[]>('/get_recommended.php');
}

// ─── Orders ───────────────────────────────────────────────────
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

// ─── Deposits ─────────────────────────────────────────────────
export async function processDeposit(amount: number, referenceId: string): Promise<DepositResponse> {
    return apiFetch<DepositResponse>('/deposit_handler.php', {
        method: 'POST',
        body: JSON.stringify({ amount, reference_id: referenceId }),
    });
}

export async function getDeposits(): Promise<Deposit[]> {
    return apiFetch<Deposit[]>('/get_deposits.php');
}

// ─── Alerts ───────────────────────────────────────────────────
export async function getAlerts(): Promise<AlertsResponse> {
    return apiFetch<AlertsResponse>('/get_alerts.php');
}

export async function markAlertsRead(): Promise<{ success: boolean }> {
    return apiFetch('/mark_alerts_read.php');
}

// ─── Chat ─────────────────────────────────────────────────────
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

// ─── Heartbeat ────────────────────────────────────────────────
export async function heartbeat(): Promise<{ ok: number }> {
    return apiFetch('/heartbeat.php');
}

// ─── Settings ────────────────────────────────────────────────
export interface AppSettings {
    rateMultiplier: number;
    discountPercent: number;
    holidayName: string;
    maintenanceMode: boolean;
    userCanOrder: boolean;
    marqueeText: string;
}

export async function getSettings(): Promise<AppSettings> {
    return apiFetch<AppSettings>('/get_settings.php');
}
