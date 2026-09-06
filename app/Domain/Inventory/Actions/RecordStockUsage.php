<?php

namespace App\Domain\Inventory\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\StockUsage;
use Illuminate\Support\Facades\DB;

/**
 * Mencatat pemakaian bahan untuk sebuah order (PRD 7.8).
 *
 * - Pemakaian EKSPLISIT dikonfirmasi teknisi/admin (bukan otomatis saat
 *   order dibuat). Mencatat stock_usages + movement negatif.
 * - Saldo dikurangi hanya bila cukup (non-negatif, ADR D8).
 * - StockUsage mencatat referensi ke order sehingga pemakaian dapat ditelusuri.
 */
final class RecordStockUsage
{
    /**
     * @param  array<int, array{stock_item_id: int, quantity: int}>  $usages
     */
    public static function record(
        ServiceOrder $order,
        int $branchId,
        array $usages,
        ?ServiceOrderItem $item = null,
        ?int $createdBy = null,
        ?string $notes = null,
    ): array {
        if ($usages === []) {
            throw new DomainConflictException('Pemakaian bahan kosong.');
        }

        return DB::transaction(function () use ($order, $branchId, $usages, $item, $createdBy, $notes) {
            $recorded = [];

            foreach ($usages as $row) {
                $stockItem = StockItem::query()->findOrFail((int) $row['stock_item_id']);
                $quantity = (int) $row['quantity'];
                if ($quantity <= 0) {
                    throw new DomainConflictException('Kuantitas pemakaian harus lebih dari nol.');
                }

                // Pengurangan akan ditolak bila saldo tidak cukup (row-lock).
                AdjustStockBalance::apply(
                    $stockItem,
                    $branchId,
                    -$quantity,
                    'usage',
                    StockUsage::class,
                    null,
                    $createdBy,
                    notes: $notes,
                );

                $usage = StockUsage::create([
                    'service_order_id' => $order->id,
                    'service_order_item_id' => $item?->id,
                    'stock_item_id' => $stockItem->id,
                    'branch_id' => $branchId,
                    'quantity' => $quantity,
                    'created_by' => $createdBy,
                    'notes' => $notes,
                ]);

                // Kaitkan movement terbaru baris ini ke usage yang baru dibuat.
                StockMovement::query()
                    ->where('reference_type', StockUsage::class)
                    ->whereNull('reference_id')
                    ->where('stock_item_id', $stockItem->id)
                    ->where('branch_id', $branchId)
                    ->orderByDesc('id')
                    ->first()
                    ?->update(['reference_id' => $usage->id]);

                $recorded[] = $usage;
            }

            return $recorded;
        });
    }
}
