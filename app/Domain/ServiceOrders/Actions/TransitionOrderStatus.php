<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Domain\ServiceOrders\State\OrderStateMachine;
use App\Exceptions\DomainConflictException;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderStatusHistory;
use Illuminate\Support\Facades\DB;

/**
 * Memvalidasi & menjalankan transisi status order dalam satu database transaction
 * (order-state-machine.md; PRD §16.6, §610).
 *
 * - Memvalidasi transisi terhadap matriks; invalid → DomainConflictException (409).
 * - Menulis baris immutable ke service_order_status_histories.
 * - Melakukan write-guard (row lock) agar transisi serentak aman.
 */
final class TransitionOrderStatus
{
    public static function transition(
        ServiceOrder $order,
        OrderStatus $to,
        ?string $reason = null,
        ?int $changedBy = null,
    ): ServiceOrder {
        $from = $order->status;

        if (! OrderStateMachine::canTransition($from, $to)) {
            throw new DomainConflictException(
                "Transisi status tidak valid: {$from->value} → {$to->value}.",
            );
        }

        if ($to === OrderStatus::Cancelled && $reason === null) {
            throw new DomainConflictException('Pembatalan wajib menyertakan alasan.');
        }

        if ($to === OrderStatus::Received && $order->items()->count() === 0) {
            throw new DomainConflictException('Order wajib memiliki minimal satu item layanan.');
        }

        return DB::transaction(function () use ($order, $from, $to, $reason, $changedBy) {
            // Row lock order agar transisi serentak tidak ganda.
            $locked = ServiceOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $locked->status = $to;
            if ($to === OrderStatus::Received) {
                $locked->received_at = $locked->received_at ?? now();
            }
            if (in_array($to, [OrderStatus::PickedUp, OrderStatus::Cancelled], true)) {
                $locked->completed_at = now();
            }
            $locked->save();

            ServiceOrderStatusHistory::create([
                'service_order_id' => $locked->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'reason' => $reason,
                'changed_by' => $changedBy,
            ]);

            return $locked;
        });
    }
}
