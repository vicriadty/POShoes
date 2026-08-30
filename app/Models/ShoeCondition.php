<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shoe_item_id',
    'area',
    'defect_type',
    'severity',
    'notes',
    'photo_id',
])]
class ShoeCondition extends Model
{
    use HasFactory;

    public function shoeItem(): BelongsTo
    {
        return $this->belongsTo(ShoeItem::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(ShoePhoto::class);
    }
}
