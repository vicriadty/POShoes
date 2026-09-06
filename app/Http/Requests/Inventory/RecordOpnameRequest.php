<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class RecordOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.stocktake') ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
            'counts' => ['required', 'array', 'min:1'],
            'counts.*.stock_item_id' => ['required', 'exists:stock_items,id'],
            'counts.*.counted' => ['required', 'integer', 'min:0'],
        ];
    }
}
