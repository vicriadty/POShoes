/**
 * API helpers untuk domain Phase 3 (customer, service catalog, service order).
 * Memakai `api` client dari ./api (Bearer token, envelope, 401 handler).
 */

import { api } from './api';
import type {
    CreateOrderPayload,
    Customer,
    Paginated,
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
