<?php

namespace App\Domain\Inventory\Actions;

use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockItem;
use Illuminate\Support\Facades\DB;

/**
 * Stock opname: menyesuaikan saldo ke hasil hitung fisik.
 */
final class RecordStockOpname
{
    /**
     * @param  array<int, array{stock_item_id: int, counted: int}>  $counts
     */
    public static function record(
        int $branchId,
        array $counts,
        int $adjustedBy,
        ?string $notes = null,
    ): StockAdjustment {
        return DB::transaction(function () use ($branchId, $counts, $adjustedBy, $notes) {
            $rows = [];
            foreach ($counts as $row) {
                $item = StockItem::query()->findOrFail((int) $row['stock_item_id']);
                $balance = StockBalance::query()
                    ->where('stock_item_id', $item->id)
                    ->where('branch_id', $branchId)
                    ->first();
                $current = $balance === null ? 0 : $balance->quantity;
                $delta = (int) $row['counted'] - $current;
                if ($delta === 0) {
                    continue;
                }
                $rows[] = [
                    'stock_item_id' => $item->id,
                    'quantity' => $delta,
                    'reason' => 'opname: fisik '.$row['counted'].', catat '.$current,
                ];
            }

            return ApplyStockAdjustment::apply(
                $branchId,
                'opname',
                'Stock opname',
                $rows,
                $adjustedBy,
                $notes,
            );
        });
    }
}
