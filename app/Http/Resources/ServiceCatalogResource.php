<?php

namespace App\Http\Resources;

use App\Models\ServiceCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceCatalog
 */
class ServiceCatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => new ServiceCategoryResource($this->category)),
            'category_id' => $this->category_id,
            'base_price' => $this->base_price,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'requires_before_after_photo' => $this->requires_before_after_photo,
            'active' => $this->active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
