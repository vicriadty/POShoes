<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class RecordUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.usage') ?? false;
    }

    public function rules(): array
    {
        return [
            'service_order_item_id' => ['nullable', 'exists:service_order_items,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'usages' => ['required', 'array', 'min:1'],
            'usages.*.stock_item_id' => ['required', 'exists:stock_items,id'],
            'usages.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
