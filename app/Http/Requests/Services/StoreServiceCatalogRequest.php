<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('services.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:service_catalogs,code'],
            'category_id' => ['required', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'integer', 'min:0'],
            'estimated_duration_minutes' => ['required', 'integer', 'min:1'],
            'requires_before_after_photo' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
