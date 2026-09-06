<?php

namespace App\Domain\Inventory\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\StockBalance;
use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Pencatatan perubahan saldo stok (inti Phase 6; business-rules non-negatif).
 *
 * Setiap mutasi:
 * - Row-lock baris stock_balances (SELECT ... FOR UPDATE) dalam transaction.
 * - Menulis baris immutable stock_movements (delta bertanda).
 * - Menolak hasil saldo negatif (ADR D8, hard rule) kecuali allowance.
 * - Memperbarui stock_balances.quantity.
 */
final class AdjustStockBalance
{
    /**
     * @param  int  $delta  delta bertanda; negatif = pengurangan.
     * @param  string  $type  stock_in|usage|adjustment|opname.
     * @param  class-string|null  $referenceClass
     */
    public static function apply(
        StockItem $item,
        int $branchId,
        int $delta,
        string $type,
        ?string $referenceClass = null,
        ?int $referenceId = null,
        ?int $createdBy = null,
        ?\DateTimeInterface $occurredAt = null,
        ?string $notes = null,
    ): StockBalance {
        if ($delta === 0) {
            throw new DomainConflictException('Delta stok tidak boleh nol.');
        }

        return DB::transaction(function () use (
            $item, $branchId, $delta, $type, $referenceClass, $referenceId, $createdBy, $occurredAt, $notes,
        ) {
            $balance = StockBalance::query()
                ->where('stock_item_id', $item->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if ($balance === null) {
                $balance = new StockBalance([
                    'stock_item_id' => $item->id,
                    'branch_id' => $branchId,
                    'quantity' => 0,
                ]);
                $balance->save();
                $balance = StockBalance::query()
                    ->where('stock_item_id', $item->id)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $newQuantity = $balance->quantity + $delta;
            if ($newQuantity < 0) {
                throw new DomainConflictException(
                    'Stok tidak cukup: saldo '.$balance->quantity.' '.$item->unit.', butuh '.abs($delta).'.',
                );
            }

            $balance->quantity = $newQuantity;
            $balance->save();

            StockMovement::create([
                'stock_item_id' => $item->id,
                'branch_id' => $branchId,
                'type' => $type,
                'quantity' => $delta,
                'reference_type' => $referenceClass,
                'reference_id' => $referenceId,
                'occurred_at' => $occurredAt ?? now(),
                'created_by' => $createdBy,
                'notes' => $notes,
            ]);

            return $balance;
        });
    }

    /**
     * @return class-string|null
     */
    public static function normalizeReference(?object $reference): ?string
    {
        return $reference === null ? null : $reference::class;
    }
}
