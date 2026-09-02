<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Domain\ServiceOrders\Enums\ItemStatus;
use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Domain\ServiceOrders\State\OrderStateMachine;
use App\Exceptions\DomainConflictException;
use App\Models\ServiceOrder;

/**
 * Sinkronisasi status order dari agregasi status item (order-state-machine.md).
 *
 * Status order dihitung dari status seluruh item, lalu "dinaikkan" langkah
 * demi langkah melalui state machine (approved → in_progress → quality_check
 * → ready_for_pickup) sampai mencapai target agregat. Perpindahan selalu lewat
 * TransitionOrderStatus agar audit trail benar; tidak pernah mundur.
 */
final class SyncOrderStatusFromItems
{
    public static function sync(ServiceOrder $order): ServiceOrder
    {
        $order->loadMissing('items');
        $items = $order->items;

        if ($items->isEmpty()) {
            return $order;
        }

        $statuses = $items->pluck('status')->map(fn (ItemStatus $s) => $s->value);
        $active = $items->whereNotIn('status', [ItemStatus::Cancelled, ItemStatus::Completed]);

        // Tentukan target agregat.
        $target = match (true) {
            $active->isEmpty() && $statuses->contains(ItemStatus::Completed->value) => OrderStatus::ReadyForPickup,
            $active->isEmpty() => OrderStatus::Cancelled,
            // Semua yang tersisa sudah selesai/QC — bukan berarti order di QC;
            // lihat aturan aktif di bawah.
            $statuses->contains(ItemStatus::QualityCheck->value)
                && ! $statuses->contains(ItemStatus::Pending->value)
                && ! $statuses->contains(ItemStatus::InProgress->value)
                && ! $statuses->contains(ItemStatus::WaitingMaterial->value) => OrderStatus::QualityCheck,
            $statuses->contains(ItemStatus::WaitingMaterial->value)
                || $statuses->contains(ItemStatus::InProgress->value)
                || $statuses->contains(ItemStatus::Pending->value) => OrderStatus::InProgress,
            default => $order->status,
        };

        return self::climb($order, $target);
    }

    private static function climb(ServiceOrder $order, OrderStatus $target): ServiceOrder
    {
        $current = $order->status;

        if ($current === $target) {
            return $order;
        }

        // Forward path yang diizinkan (produksi).
        $forward = [
            OrderStatus::Approved->value => OrderStatus::InProgress,
            OrderStatus::InProgress->value => OrderStatus::QualityCheck,
            OrderStatus::QualityCheck->value => OrderStatus::ReadyForPickup,
        ];

        $order = $order->refresh();
        $current = $order->status;
        $guard = 0;

        while ($current !== $target && $guard++ < 8) {
            $next = $forward[$current->value] ?? null;

            if ($next === null || ! OrderStateMachine::canTransition($current, $next)) {
                break;
            }

            try {
                $order = TransitionOrderStatus::transition($order, $next);
            } catch (DomainConflictException) {
                break;
            }

            $current = $order->status;
        }

        return $order;
    }
}
