<?php

namespace App\Http\Resources\Inventory;

use App\Models\StockAdjustmentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockAdjustmentItem
 */
class StockAdjustmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stock_item_id' => $this->stock_item_id,
            'quantity' => $this->quantity,
            'reason' => $this->reason,
        ];
    }
}
