<?php

namespace App\Http\Resources\Inventory;

use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockAdjustment
 */
class StockAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adjustment_number' => $this->adjustment_number,
            'branch_id' => $this->branch_id,
            'type' => $this->type,
            'reason' => $this->reason,
            'adjusted_at' => $this->adjusted_at->toISOString(),
            'adjusted_by' => $this->adjusted_by,
            'notes' => $this->notes,
            'items' => StockAdjustmentItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
