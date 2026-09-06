<?php

namespace App\Http\Resources\Inventory;

use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockItem
 */
class StockItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $balance = $this->balances->first();
        $quantity = $balance === null ? 0 : $balance->quantity;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'unit' => $this->unit,
            'min_stock' => $this->min_stock,
            'active' => $this->active,
            'notes' => $this->notes,
            'quantity' => $quantity,
            'low_stock' => $quantity <= $this->min_stock,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
