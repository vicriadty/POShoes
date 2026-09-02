<?php

namespace App\Models;

use App\Domain\ServiceOrders\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'service_order_id',
    'service_catalog_id',
    'service_name',
    'quantity',
    'unit_price',
    'discount_amount',
    'subtotal',
    'estimated_duration_minutes',
    'status',
    'notes',
    'assigned_to',
    'price_approved_by',
    'price_approved_at',
])]
class ServiceOrderItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ItemStatus::class,
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'discount_amount' => 'integer',
            'subtotal' => 'integer',
            'estimated_duration_minutes' => 'integer',
            'price_approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceOrder, $this>
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function serviceCatalog(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function shoes(): BelongsToMany
    {
        return $this->belongsToMany(ShoeItem::class, 'order_item_shoes')
            ->withPivot(['quantity', 'notes'])
            ->withTimestamps();
    }
}
