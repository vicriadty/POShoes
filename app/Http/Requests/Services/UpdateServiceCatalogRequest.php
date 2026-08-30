<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('services.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('service_catalogs', 'code')->ignore($this->route('service')),
            ],
            'category_id' => ['sometimes', 'exists:service_categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['sometimes', 'integer', 'min:0'],
            'estimated_duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'requires_before_after_photo' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
