<?php

namespace App\Http\Resources;

use App\Models\ShoeItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShoeItem
 */
class ShoeItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'color' => $this->color,
            'size' => $this->size,
            'material' => $this->material,
            'condition_summary' => $this->condition_summary,
            'customer_description' => $this->customer_description,
            'internal_description' => $this->internal_description,
            'photos' => ShoePhotoResource::collection($this->whenLoaded('photos')),
            'order_item_ids' => $this->whenLoaded('orderItems', fn () => $this->orderItems->pluck('id')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
