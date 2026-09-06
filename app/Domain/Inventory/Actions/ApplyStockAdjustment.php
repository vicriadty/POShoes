<?php

namespace App\Domain\Inventory\Actions;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockItem;
use Illuminate\Support\Facades\DB;

/**
 * Adjustment stok (restock / opname / correction).
 *
 * - Satu header adjustment + beberapa baris item (delta).
 * - Setiap baris memanggil AdjustStockBalance dalam satu transaction;
 *   bila satu baris gagal (negatif), seluruh adjustment digagalkan (rollback).
 */
final class ApplyStockAdjustment
{
    /**
     * @param  array<int, array{stock_item_id: int, quantity: int, reason?: string|null}>  $items
     */
    public static function apply(
        int $branchId,
        string $type,
        string $reason,
        array $items,
        int $adjustedBy,
        ?string $notes = null,
    ): StockAdjustment {
        return DB::transaction(function () use ($branchId, $type, $reason, $items, $adjustedBy, $notes) {
            $adjustment = StockAdjustment::create([
                'adjustment_number' => self::number(),
                'branch_id' => $branchId,
                'type' => $type,
                'reason' => $reason,
                'adjusted_at' => now(),
                'adjusted_by' => $adjustedBy,
                'notes' => $notes,
            ]);

            foreach ($items as $row) {
                $item = StockItem::query()->findOrFail((int) $row['stock_item_id']);
                $delta = (int) $row['quantity'];
                if ($delta === 0) {
                    continue;
                }

                AdjustStockBalance::apply(
                    $item,
                    $branchId,
                    $delta,
                    'adjustment',
                    StockAdjustment::class,
                    $adjustment->id,
                    $adjustedBy,
                );

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'stock_item_id' => $item->id,
                    'quantity' => $delta,
                    'reason' => $row['reason'] ?? null,
                ]);
            }

            return $adjustment->load('items');
        });
    }

    private static function number(): string
    {
        return 'ADJ-'.now()->format('YmdHis').'-'.strtoupper(substr(uniqid('', true), -4));
    }
}
