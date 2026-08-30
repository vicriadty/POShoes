<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'phone_wa',
    'phone_wa_normalized',
    'email',
    'address',
    'notes',
    'communication_consent_at',
    'created_by',
])]
class Customer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'communication_consent_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
