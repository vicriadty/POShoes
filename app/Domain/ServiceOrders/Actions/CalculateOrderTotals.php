<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Models\ServiceOrder;

/**
 * Menghitung total order dari snapshot item (business-rules §1).
 *
 * - subtotal = Σ (unit_price × quantity) per item.
 * - discount_amount & tax_amount dari input (integer rupiah).
 * - total_amount = subtotal - discount + tax.
 * - paid_amount dipertahankan; remaining_amount = total_amount - paid_amount.
 *
 * Semua nominal integer rupiah (PRD §267). Tidak ada floating point.
 */
final class CalculateOrderTotals
{
    /**
     * @param  array<int, array{subtotal: int}>  $items
     */
    public static function recalculate(ServiceOrder $order, array $items, ?int $discountAmount = null, ?int $taxAmount = null): void
    {
        $subtotal = array_sum(array_map(
            static fn (array $item): int => (int) $item['subtotal'],
            $items,
        ));

        $discount = $discountAmount ?? (int) $order->discount_amount;
        $tax = $taxAmount ?? (int) $order->tax_amount;

        $total = max(0, $subtotal - $discount + $tax);
        $paid = (int) $order->paid_amount;

        $order->subtotal = $subtotal;
        $order->discount_amount = $discount;
        $order->tax_amount = $tax;
        $order->total_amount = $total;
        $order->remaining_amount = max(0, $total - $paid);
    }
}
