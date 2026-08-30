<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_order_id' => $this->service_order_id,
            'payment_number' => $this->payment_number,
            'payment_method_id' => $this->payment_method_id,
            'method' => new PaymentMethodResource($this->whenLoaded('method')),
            'amount' => $this->amount,
            'received_at' => $this->received_at->toISOString(),
            'received_by' => $this->received_by,
            'reference' => $this->reference,
            'voided_by' => $this->voided_by,
            'voided_at' => $this->voided_at?->toISOString(),
            'void_reason' => $this->void_reason,
            'refunded_from' => $this->refunded_from,
            'is_voided' => $this->isVoided(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
