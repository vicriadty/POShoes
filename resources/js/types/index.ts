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
    payments?: Payment[];
    invoices?: Invoice[];
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

// ===== Payment =====

export interface PaymentMethod {
    id: number;
    code: string;
    name: string;
    type: string;
    active: boolean;
}

export interface Payment {
    id: number;
    service_order_id: number;
    payment_number: string;
    payment_method_id: number;
    method?: PaymentMethod | null;
    amount: number;
    received_at: string;
    received_by: number;
    reference: string | null;
    voided_by: number | null;
    voided_at: string | null;
    void_reason: string | null;
    refunded_from: number | null;
    is_voided: boolean;
    created_at: string | null;
}

export interface Invoice {
    id: number;
    invoice_number: string;
    service_order_id: number;
    status: string;
    issued_at: string | null;
    due_at: string | null;
    sent_at: string | null;
    created_at: string | null;
}

// ===== Cashier shift =====

export interface CashierShift {
    id: number;
    user_id: number;
    branch_id: number;
    opening_balance: number;
    closed_balance: number | null;
    expected_amount: number | null;
    discrepancy: number | null;
    opened_at: string;
    closed_at: string | null;
    notes: string | null;
    is_open: boolean;
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
