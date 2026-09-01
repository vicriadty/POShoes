/**
 * API helpers untuk domain Phase 3 (customer, service catalog, service order).
 * Memakai `api` client dari ./api (Bearer token, envelope, 401 handler).
 */

import { api, ApiError } from './api';
import type {
    CreateOrderPayload,
    Customer,
    Paginated,
    CashierShift,
    Invoice,
    Payment,
    PaymentMethod,
    ServiceCatalog,
    ServiceCategory,
    ServiceOrder,
} from '@/types';

function toQuery(params: Record<string, string | number | boolean | undefined>): string {
    const qs = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== '') {
            qs.set(key, String(value));
        }
    }
    const q = qs.toString();

    return q ? `?${q}` : '';
}

// ===== Customer =====

export async function fetchCustomers(params: { search?: string; page?: number; per_page?: number } = {}): Promise<Paginated<Customer>> {
    return api.get<Paginated<Customer>>(`/customers${toQuery(params)}`);
}

export function createCustomer(payload: { name: string; phone_wa: string; email?: string; address?: string; notes?: string }): Promise<{ data: Customer }> {
    return api.post<{ data: Customer }>('/customers', payload);
}

// ===== Service catalog =====

export async function fetchServices(params: { active_only?: boolean; search?: string; page?: number; per_page?: number } = {}): Promise<Paginated<ServiceCatalog>> {
    const merged = params.active_only ? { ...params, active_only: 1 } : params;
    return api.get<Paginated<ServiceCatalog>>(`/services${toQuery(merged)}`);
}

export async function fetchCategories(): Promise<{ data: ServiceCategory[] }> {
    return api.get<{ data: ServiceCategory[] }>('/services/categories?active_only=1');
}

// ===== Service order =====

export async function fetchOrders(params: { status?: string; search?: string; page?: number; per_page?: number } = {}): Promise<Paginated<ServiceOrder>> {
    return api.get<Paginated<ServiceOrder>>(`/service-orders${toQuery(params)}`);
}

export async function fetchOrder(id: number): Promise<{ data: ServiceOrder }> {
    return api.get<{ data: ServiceOrder }>(`/service-orders/${id}`);
}

export function createOrder(payload: CreateOrderPayload): Promise<{ data: ServiceOrder }> {
    return api.post<{ data: ServiceOrder }>('/service-orders', payload);
}

export function changeOrderStatus(id: number, status: string, reason?: string): Promise<{ data: ServiceOrder }> {
    return api.post<{ data: ServiceOrder }>(`/service-orders/${id}/status`, { status, reason });
}

export function pickupOrder(id: number): Promise<{ data: ServiceOrder }> {
    return api.post<{ data: ServiceOrder }>(`/service-orders/${id}/pickup`);
}

// ===== Payment =====

export async function fetchPaymentMethods(params: { active_only?: boolean } = {}): Promise<{ data: PaymentMethod[] }> {
    return api.get<{ data: PaymentMethod[] }>(`/payment-methods${params.active_only ? '?active_only=1' : ''}`);
}

export async function fetchPayments(orderId: number): Promise<{ data: Payment[] }> {
    return api.get<{ data: Payment[] }>(`/service-orders/${orderId}/payments`);
}

export function createPayment(orderId: number, payload: { payment_method_id: number; amount: number; reference?: string }): Promise<{ data: Payment }> {
    return api.post<{ data: Payment }>(`/service-orders/${orderId}/payments`, payload);
}

export function voidPayment(orderId: number, paymentId: number, reason?: string): Promise<{ data: Payment }> {
    return api.post<{ data: Payment }>(`/service-orders/${orderId}/payments/${paymentId}/void`, { reason });
}

export function refundPayment(orderId: number, paymentId: number, payload: { payment_method_id: number; amount: number; reference?: string }): Promise<{ data: Payment }> {
    return api.post<{ data: Payment }>(`/service-orders/${orderId}/payments/${paymentId}/refund`, payload);
}

// ===== Invoice =====

export async function fetchInvoice(orderId: number): Promise<{ data: Invoice }> {
    return api.get<{ data: Invoice }>(`/service-orders/${orderId}/invoice`);
}

export async function sendInvoice(orderId: number): Promise<{ data: Invoice }> {
    return api.post<{ data: Invoice }>(`/service-orders/${orderId}/invoice/send`);
}

/**
 * Unduh invoice PDF dengan header Authorization Bearer (tidak bisa via
 * window.open — tab baru tidak membawa token, memicu 401/redirect login).
 * Mengembalikan URL objek blob untuk dibuka/diunduh.
 */
export async function downloadInvoicePdf(orderId: number): Promise<string> {
    const token = localStorage.getItem('poshoes_token');
    const response = await fetch(`/api/v1/service-orders/${orderId}/invoice/pdf`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
    });

    if (!response.ok) {
        const body = await response.json().catch(() => null);
        throw new ApiError(response.status, body?.message ?? 'Gagal mengunduh invoice.', body?.errors);
    }

    const blob = await response.blob();
    return URL.createObjectURL(blob);
}

// ===== Cashier shift =====

export async function fetchCurrentShift(): Promise<{ data: CashierShift | null }> {
    return api.get<{ data: CashierShift | null }>('/cashier-shifts/current');
}

export function openShift(payload: { opening_balance: number; notes?: string }): Promise<{ data: CashierShift }> {
    return api.post<{ data: CashierShift }>('/cashier-shifts', payload);
}

export function closeShift(shiftId: number, payload: { closed_balance: number; notes?: string }): Promise<{ data: CashierShift }> {
    return api.post<{ data: CashierShift }>(`/cashier-shifts/${shiftId}/close`, payload);
}
