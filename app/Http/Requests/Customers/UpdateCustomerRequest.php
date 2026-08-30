<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.update') ?? false;
    }

    public function rules(): array
    {
        /** @var Customer|null $current */
        $current = $this->route('customer');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone_wa' => [
                'sometimes',
                'string',
                'max:20',
                function (string $attribute, string $value, $fail) use ($current): void {
                    if (Customer::query()
                        ->where('phone_wa_normalized', PhoneNormalizer::normalize($value))
                        ->when($current, fn ($q) => $q->where('id', '!=', $current->id))
                        ->exists()
                    ) {
                        $fail('Nomor WhatsApp sudah terdaftar pada pelanggan lain.');
                    }
                },
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'communication_consent' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->filled('phone_wa')) {
            $this->merge([
                'phone_wa_normalized' => PhoneNormalizer::normalize((string) $this->input('phone_wa')),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'phone_wa.unique' => 'Nomor WhatsApp sudah terdaftar pada pelanggan lain.',
        ];
    }
}
