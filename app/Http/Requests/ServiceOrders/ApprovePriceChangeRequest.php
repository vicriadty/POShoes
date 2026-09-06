<?php

namespace App\Http\Requests\ServiceOrders;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePriceChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service_orders.approve') ?? false;
    }

    public function rules(): array
    {
        return [
            'approved_price' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
