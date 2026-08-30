<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'category_id',
    'name',
    'description',
    'base_price',
    'estimated_duration_minutes',
    'requires_before_after_photo',
    'active',
])]
class ServiceCatalog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'estimated_duration_minutes' => 'integer',
            'requires_before_after_photo' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class, 'service_catalog_id');
    }
}
