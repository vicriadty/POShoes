<?php

namespace App\Http\Requests\ServiceOrders;

use App\Domain\ServiceOrders\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service_orders.change_status') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'reason' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:status,cancelled',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status tujuan wajib diisi.',
            'status.Illuminate\Validation\Rules\Enum' => 'Status tujuan tidak valid.',
        ];
    }
}
