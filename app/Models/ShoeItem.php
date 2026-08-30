<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'service_order_id',
    'brand',
    'model',
    'color',
    'size',
    'material',
    'condition_summary',
    'customer_description',
    'internal_description',
])]
class ShoeItem extends Model
{
    use HasFactory;

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ShoePhoto::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(ShoeCondition::class);
    }

    public function orderItems(): BelongsToMany
    {
        return $this->belongsToMany(ServiceOrderItem::class, 'order_item_shoes')
            ->withPivot(['quantity', 'notes'])
            ->withTimestamps();
    }
}
