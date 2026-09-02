<?php

namespace App\Http\Requests\TechnicianWork;

use App\Domain\ServiceOrders\Enums\ItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('work.item_status') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ItemStatus::class)],
            'reason' => ['nullable', 'string', 'max:2000', 'required_if:status,cancelled'],
        ];
    }
}
