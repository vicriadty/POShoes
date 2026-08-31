<?php

namespace App\Domain\Payments\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;

/**
 * Mengembalikan dana (refund) sebagian atau seluruh dari sebuah pembayaran.
 *
 * Refund direpresentasikan sebagai payment baru bernilai negatif yang merujuk
 * ke payment asal (refunded_from). Nilai paid_amount order dihitung ulang dari
 * seluruh payment non-void (termasuk yang negatif).
 */
final class RefundPayment
{
    public static function refund(
        Payment $sourcePayment,
        PaymentMethod $method,
        int $amount,
        int $refundedBy,
        ?string $reference = null,
        ?string $note = null,
    ): Payment {
        if ($amount <= 0) {
            throw new DomainConflictException('Jumlah refund harus lebih dari nol.');
        }

        return DB::transaction(function () use ($sourcePayment, $method, $amount, $refundedBy, $reference) {
            $source = Payment::query()
                ->whereKey($sourcePayment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($source->isVoided()) {
                throw new DomainConflictException('Tidak dapat me-refund pembayaran yang sudah di-void.');
            }

            // Refund hanya dari pembayaran positif (bukan dari refund sebelumnya).
            if ($source->amount <= 0) {
                throw new DomainConflictException('Refund hanya dapat berasal dari pembayaran masuk.');
            }

            // Sisa yang masih bisa di-refund = amount asli - total refund yang sudah keluar.
            $refundedSoFar = (int) Payment::query()
                ->where('refunded_from', $source->id)
                ->whereNull('voided_at')
                ->sum('amount'); // negatif

            $refundable = $source->amount - abs($refundedSoFar);

            if ($amount > $refundable) {
                throw new DomainConflictException(
                    'Refund melebihi sisa yang dapat dikembalikan (Rp '.number_format($refundable, 0, ',', '.').').',
                );
            }

            $refund = Payment::create([
                'service_order_id' => $source->service_order_id,
                'payment_method_id' => $method->id,
                'payment_number' => GeneratePaymentNumber::generate(),
                'amount' => -$amount,
                'received_at' => now(),
                'received_by' => $refundedBy,
                'reference' => $reference,
                'refunded_from' => $source->id,
            ]);

            $order = ServiceOrder::query()
                ->whereKey($source->service_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            RecalculateOrderPayments::recalculate($order);

            return $refund;
        });
    }
}
