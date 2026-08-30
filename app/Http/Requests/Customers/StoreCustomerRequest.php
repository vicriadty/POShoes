<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_wa' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, string $value, $fail): void {
                    if (Customer::query()
                        ->where('phone_wa_normalized', PhoneNormalizer::normalize($value))
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
        $phone = (string) $this->input('phone_wa');
        $this->merge([
            'phone_wa_normalized' => $phone === '' ? '' : PhoneNormalizer::normalize($phone),
            'communication_consent_at' => $this->boolean('communication_consent') ? now() : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'phone_wa.unique' => 'Nomor WhatsApp sudah terdaftar pada pelanggan lain.',
        ];
    }
}
