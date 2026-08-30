<?php

namespace App\Http\Resources;

use App\Models\ServiceOrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceOrderStatusHistory
 */
class ServiceOrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_order_item_id' => $this->service_order_item_id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'reason' => $this->reason,
            'changed_by' => $this->changed_by,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
