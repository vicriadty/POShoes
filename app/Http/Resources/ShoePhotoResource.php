<?php

namespace App\Http\Resources;

use App\Models\ShoePhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShoePhoto
 */
class ShoePhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'file_path' => $this->file_path,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'captured_by' => $this->captured_by,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
