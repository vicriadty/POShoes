<?php

namespace App\Domain\CashierShifts\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\CashierShift;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Menutup shift kasir.
 *
 * - expected_amount = opening_balance + Σ payments masuk - Σ refund (non-void,
 *   dalam rentang shift).
 * - discrepancy = closed_balance (kas fisik dihitung user) - expected_amount.
 * - Shift yang sudah ditutup tidak bisa ditutup lagi.
 */
final class CloseShift
{
    public static function close(
        CashierShift $shift,
        int $closedBalance,
        ?string $notes = null,
    ): CashierShift {
        return DB::transaction(function () use ($shift, $closedBalance, $notes) {
            $locked = CashierShift::query()
                ->whereKey($shift->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->closed_at !== null) {
                throw new DomainConflictException('Shift ini sudah ditutup.');
            }

            $paymentsSum = (int) Payment::query()
                ->where('received_by', $locked->user_id)
                ->whereNull('voided_at')
                ->where('received_at', '>=', $locked->opened_at)
                ->sum('amount');

            $expected = $locked->opening_balance + $paymentsSum;

            $locked->closed_balance = $closedBalance;
            $locked->expected_amount = $expected;
            $locked->discrepancy = $closedBalance - $expected;
            $locked->closed_at = now();
            $locked->notes = $notes !== null ? $notes : $locked->notes;
            $locked->save();

            return $locked;
        });
    }
}
