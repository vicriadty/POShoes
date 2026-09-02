<?php

namespace App\Http\Requests\TechnicianWork;

use Illuminate\Foundation\Http\FormRequest;

class AddItemNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('work.notes') ?? false;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:4000'],
            'append' => ['nullable', 'boolean'],
        ];
    }
}
