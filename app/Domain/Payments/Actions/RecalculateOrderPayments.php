<?php

namespace App\Domain\Payments\Actions;

use App\Models\ServiceOrder;

/**
 * Menghitung ulang paid_amount & remaining_amount dari payments yang valid
 * (tidak void) pada satu order (business-rules §1; ADR D4).
 *
 * paid_amount = Σ amount (payment non-void, termasuk refund negatif).
 * remaining_amount = max(0, total_amount - paid_amount).
 *
 * Selalu dipanggil dalam database transaction dengan row lock order.
 */
final class RecalculateOrderPayments
{
    public static function recalculate(ServiceOrder $order): void
    {
        $paid = (int) $order->payments()
            ->whereNull('voided_at')
            ->sum('amount');

        $order->paid_amount = max(0, $paid);
        $order->remaining_amount = max(0, $order->total_amount - $order->paid_amount);
        $order->save();
    }
}
