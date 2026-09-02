<?php

namespace App\Http\Resources;

use App\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource ringkas untuk work queue teknisi (Phase 5).
 *
 * @mixin ServiceOrderItem
 */
class WorkItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_order_id' => $this->service_order_id,
            'service_order_number' => $this->whenLoaded('serviceOrder', fn () => $this->serviceOrder->order_number),
            'customer_name' => $this->whenLoaded('serviceOrder', fn () => $this->serviceOrder->customer?->name),
            'service_catalog_id' => $this->service_catalog_id,
            'service_name' => $this->service_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'assigned_to' => $this->assigned_to,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'price_approved_by' => $this->price_approved_by,
            'price_approved_at' => $this->price_approved_at?->toISOString(),
            'order_status' => $this->whenLoaded('serviceOrder', fn () => $this->serviceOrder->status->value),
            'estimated_completed_at' => $this->whenLoaded('serviceOrder', fn () => $this->serviceOrder->estimated_completed_at?->toISOString()),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
