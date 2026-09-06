<?php

namespace App\Http\Requests\ServiceOrders;

use Illuminate\Foundation\Http\FormRequest;

class RequestPriceChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service_orders.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'unit_price' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
