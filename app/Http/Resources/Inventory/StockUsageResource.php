<?php

namespace App\Http\Resources\Inventory;

use App\Models\StockUsage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockUsage
 */
class StockUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_order_id' => $this->service_order_id,
            'service_order_item_id' => $this->service_order_item_id,
            'stock_item_id' => $this->stock_item_id,
            'quantity' => $this->quantity,
            'created_by' => $this->created_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
