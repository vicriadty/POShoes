<?php

namespace App\Http\Resources;

use App\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceOrderItem
 */
class ServiceOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_catalog_id' => $this->service_catalog_id,
            'service_name' => $this->service_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount_amount' => $this->discount_amount,
            'subtotal' => $this->subtotal,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'assigned_to' => $this->assigned_to,
            'price_approved_by' => $this->price_approved_by,
            'price_approved_at' => $this->price_approved_at?->toISOString(),
            'shoes' => $this->whenLoaded('shoes', fn () => $this->shoes->pluck('id')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
