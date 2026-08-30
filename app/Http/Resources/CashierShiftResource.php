<?php

namespace App\Http\Resources;

use App\Models\CashierShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CashierShift
 */
class CashierShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'opening_balance' => $this->opening_balance,
            'closed_balance' => $this->closed_balance,
            'expected_amount' => $this->expected_amount,
            'discrepancy' => $this->discrepancy,
            'opened_at' => $this->opened_at->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'notes' => $this->notes,
            'is_open' => $this->closed_at === null,
        ];
    }
}
