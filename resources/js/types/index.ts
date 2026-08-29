/**
 * Tipe bersama untuk frontend POShoes.
 */

export interface DraftOrderItem {
    serviceName: string;
    quantity: number;
    unitPrice: number;
    note?: string;
}

export interface DraftOrder {
    id?: number;
    customerName: string;
    customerPhone: string;
    notes: string;
    items: DraftOrderItem[];
    createdAt: number;
    updatedAt: number;
    synced: boolean;
    syncedAt?: number;
}

export interface OrderSummary {
    id: number;
    order_number: string;
    customer_name: string;
    status: string;
    total_amount: number;
    remaining_amount: number;
    received_at: string | null;
    estimated_completed_at: string | null;
}
