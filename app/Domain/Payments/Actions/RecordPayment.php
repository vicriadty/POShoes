<?php

namespace App\Domain\Payments\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;

/**
 * Mencatat pembayaran (DP, pembayaran penuh, pelunasan) untuk satu order.
 *
 * - Row-lock order dalam database transaction (anti race dengan pickup/void).
 * - Validasi: amount > 0, payment method aktif, amount <= remaining_amount.
 * - Order harus open (belum picked_up/cancelled).
 * - Menghitung ulang paid_amount / remaining_amount.
 */
final class RecordPayment
{
    public static function record(
        ServiceOrder $order,
        PaymentMethod $method,
        int $amount,
        int $receivedBy,
        ?string $reference = null,
        ?\DateTimeInterface $receivedAt = null,
    ): Payment {
        if ($amount <= 0) {
            throw new DomainConflictException('Jumlah pembayaran harus lebih dari nol.');
        }

        if (! $method->active) {
            throw new DomainConflictException("Metode pembayaran '{$method->name}' tidak aktif.");
        }

        if (! $order->status->isOpen()) {
            throw new DomainConflictException(
                'Pembayaran tidak dapat diterima: order sudah '.$order->status->value.'.',
            );
        }

        return DB::transaction(function () use ($order, $method, $amount, $receivedBy, $reference, $receivedAt) {
            $locked = ServiceOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($amount > $locked->remaining_amount) {
                throw new DomainConflictException(
                    'Pembayaran melebihi sisa tagihan (Rp '.number_format($locked->remaining_amount, 0, ',', '.').').',
                );
            }

            $payment = Payment::create([
                'service_order_id' => $locked->id,
                'payment_method_id' => $method->id,
                'payment_number' => GeneratePaymentNumber::generate($receivedAt),
                'amount' => $amount,
                'received_at' => $receivedAt ?? now(),
                'received_by' => $receivedBy,
                'reference' => $reference,
            ]);

            RecalculateOrderPayments::recalculate($locked);

            return $payment;
        });
    }
}
