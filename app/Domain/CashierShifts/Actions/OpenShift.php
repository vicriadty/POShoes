<?php

namespace App\Domain\CashierShifts\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\CashierShift;
use Illuminate\Support\Facades\DB;

/**
 * Membuka shift kasir.
 *
 * - Satu user hanya boleh memiliki satu shift aktif (belum ditutup) per waktu.
 * - opening_balance = kas awal (integer rupiah).
 */
final class OpenShift
{
    public static function open(
        int $userId,
        int $branchId,
        int $openingBalance,
        ?string $notes = null,
    ): CashierShift {
        return DB::transaction(function () use ($userId, $branchId, $openingBalance, $notes) {
            $existing = CashierShift::query()
                ->where('user_id', $userId)
                ->whereNull('closed_at')
                ->exists();

            if ($existing) {
                throw new DomainConflictException('Masih ada shift aktif untuk user ini. Tutup shift terlebih dahulu.');
            }

            return CashierShift::create([
                'user_id' => $userId,
                'branch_id' => $branchId,
                'opening_balance' => max(0, $openingBalance),
                'opened_at' => now(),
                'notes' => $notes,
            ]);
        });
    }
}
