<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'branch_id',
    'opening_balance',
    'closed_balance',
    'expected_amount',
    'discrepancy',
    'opened_at',
    'closed_at',
    'notes',
])]
class CashierShift extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'opening_balance' => 'integer',
            'closed_balance' => 'integer',
            'expected_amount' => 'integer',
            'discrepancy' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
