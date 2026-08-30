<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Support\PhoneNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_wa' => $this->phone_wa,
            'phone_wa_normalized' => $this->phone_wa_normalized,
            'phone_wa_international' => PhoneNormalizer::displayInternational($this->phone_wa_normalized),
            'email' => $this->email,
            'address' => $this->address,
            'notes' => $this->notes,
            'communication_consent_at' => $this->communication_consent_at?->toISOString(),
            'order_count' => $this->whenCounted('orders'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
