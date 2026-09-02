<?php

namespace App\Http\Requests\TechnicianWork;

use Illuminate\Foundation\Http\FormRequest;

class AssignItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service_orders.assign') ?? false;
    }

    public function rules(): array
    {
        return [
            'technician_id' => ['required', 'exists:users,id'],
        ];
    }
}
