<?php

namespace App\Http\Requests\ServiceOrders;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service_orders.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'estimated_completed_at' => ['nullable', 'date', 'after_or_equal:today'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],

            // Items: minimal satu layanan (business-rules / state-machine).
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_catalog_id' => ['required', 'integer', 'exists:service_catalogs,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.shoe_ids' => ['nullable', 'array'],
            'items.*.shoe_ids.*' => ['integer', 'exists:shoe_items,id'],

            // Shoes (opsional pada draft; wajib sebelum publish).
            'shoes' => ['nullable', 'array'],
            'shoes.*.brand' => ['nullable', 'string', 'max:255'],
            'shoes.*.model' => ['nullable', 'string', 'max:255'],
            'shoes.*.color' => ['nullable', 'string', 'max:100'],
            'shoes.*.size' => ['nullable', 'string', 'max:50'],
            'shoes.*.material' => ['nullable', 'string', 'max:255'],
            'shoes.*.condition_summary' => ['nullable', 'string', 'max:2000'],
            'shoes.*.customer_description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
