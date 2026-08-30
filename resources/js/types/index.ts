/**
 * Tipe bersama untuk frontend POShoes.
 * Menyesuaikan resource backend (app/Http/Resources).
 */

// ===== Draft order lokal (IndexedDB/Dexie) =====

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

// ===== Customer =====

export interface Customer {
    id: number;
    name: string;
    phone_wa: string;
    phone_wa_normalized: string;
    phone_wa_international: string;
    email: string | null;
    address: string | null;
    notes: string | null;
    communication_consent_at: string | null;
    order_count?: number;
    created_at: string | null;
    updated_at: string | null;
}

// ===== Service catalog =====

export interface ServiceCategory {
    id: number;
    name: string;
    code: string;
    active: boolean;
}

export interface ServiceCatalog {
    id: number;
    code: string;
    name: string;
    description: string | null;
    category_id: number;
    category?: ServiceCategory | null;
    base_price: number;
    estimated_duration_minutes: number;
    requires_before_after_photo: boolean;
    active: boolean;
}

// ===== Service order =====

export interface ServiceOrderItem {
    id: number;
    service_catalog_id: number;
    service_name: string;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    subtotal: number;
    estimated_duration_minutes: number | null;
    status: string;
    notes: string | null;
    assigned_to: number | null;
    price_approved_by: number | null;
    price_approved_at: string | null;
    shoes?: number[];
}

export interface ShoeItem {
    id: number;
    brand: string | null;
    model: string | null;
    color: string | null;
    size: string | null;
    material: string | null;
    condition_summary: string | null;
    customer_description: string | null;
    internal_description: string | null;
}

export interface ServiceOrderStatusHistory {
    id: number;
    service_order_item_id: number | null;
    from_status: string | null;
    to_status: string;
    reason: string | null;
    changed_by: number | null;
    created_at: string | null;
}

export interface ServiceOrder {
    id: number;
    order_number: string;
    status: string;
    customer_id: number;
    branch_id: number;
    received_by: number;
    received_at: string | null;
    estimated_completed_at: string | null;
    completed_at: string | null;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total_amount: number;
    paid_amount: number;
    remaining_amount: number;
    customer_notes: string | null;
    internal_notes: string | null;
    customer?: Customer | null;
    items?: ServiceOrderItem[];
    shoes?: ShoeItem[];
    status_histories?: ServiceOrderStatusHistory[];
    created_at: string | null;
    updated_at: string | null;
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

// ===== Envelope & pagination =====

export interface Paginated<T> {
    data: T[];
    meta: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
    };
}

// ===== Payload pembuatan order =====

export interface CreateOrderPayload {
    customer_id: number;
    items: Array<{
        service_catalog_id: number;
        quantity?: number;
        notes?: string | null;
        shoe_ids?: number[];
    }>;
    shoes?: Array<{
        brand?: string | null;
        model?: string | null;
        color?: string | null;
        size?: string | null;
        material?: string | null;
        condition_summary?: string | null;
        customer_description?: string | null;
    }>;
    customer_notes?: string | null;
    internal_notes?: string | null;
    estimated_completed_at?: string | null;
    discount_amount?: number;
    tax_amount?: number;
}
