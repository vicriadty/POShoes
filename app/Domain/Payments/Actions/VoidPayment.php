<?php

namespace App\Domain\Payments\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\Payment;
use App\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;

/**
 * Membatalkan (void) sebuah pembayaran.
 *
 * - Tidak menghapus baris; menandai voided_at + voided_by + void_reason (audit).
 * - Recalculate paid_amount / remaining_amount order.
 * - Pembayaran refund (negatif) juga bisa di-void.
 */
final class VoidPayment
{
    public static function void(
        Payment $payment,
        int $voidedBy,
        ?string $reason = null,
    ): Payment {
        return DB::transaction(function () use ($payment, $voidedBy, $reason) {
            $locked = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isVoided()) {
                throw new DomainConflictException('Pembayaran sudah dibatalkan (void) sebelumnya.');
            }

            $locked->voided_by = $voidedBy;
            $locked->voided_at = now();
            $locked->void_reason = $reason;
            $locked->save();

            $order = ServiceOrder::query()
                ->whereKey($locked->service_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            RecalculateOrderPayments::recalculate($order);

            return $locked;
        });
    }
}
