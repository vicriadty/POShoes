<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Domain\ServiceOrders\Enums\ItemStatus;
use App\Domain\ServiceOrders\State\ItemStateMachine;
use App\Exceptions\DomainConflictException;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceOrderStatusHistory;
use Illuminate\Support\Facades\DB;

/**
 * Transisi status satu item layanan (Phase 5; order-state-machine.md).
 *
 * - Memvalidasi transisi terhadap ItemStateMachine.
 * - Guard: item pending → in_progress wajib sudah di-assign ke teknisi.
 * - Menulis baris immutable service_order_status_histories (dengan
 *   service_order_item_id terisi).
 * - Setelah transisi, sinkronkan status order dari status seluruh item
 *   (agregasi, lihat SyncOrderStatusFromItems).
 */
final class TransitionItemStatus
{
    public static function transition(
        ServiceOrderItem $item,
        ItemStatus $to,
        ?string $reason = null,
        ?int $changedBy = null,
    ): ServiceOrderItem {
        if ($to === ItemStatus::Cancelled && $reason === null) {
            throw new DomainConflictException('Pembatalan item wajib menyertakan alasan.');
        }

        return DB::transaction(function () use ($item, $to, $reason, $changedBy) {
            $locked = ServiceOrderItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $from = $locked->status;

            if (! ItemStateMachine::canTransition($from, $to)) {
                throw new DomainConflictException(
                    "Transisi status item tidak valid: {$from->value} → {$to->value}.",
                );
            }

            // pending → in_progress butuh assignee (order-state-machine guard).
            if ($to === ItemStatus::InProgress && $from === ItemStatus::Pending && $locked->assigned_to === null) {
                throw new DomainConflictException('Item wajib di-assign ke teknisi sebelum dikerjakan.');
            }

            if ($to === ItemStatus::InProgress && $from === ItemStatus::QualityCheck && $reason === null) {
                throw new DomainConflictException('Rework (QC → in_progress) wajib menyertakan alasan.');
            }

            $locked->status = $to;
            $locked->save();

            ServiceOrderStatusHistory::create([
                'service_order_id' => $locked->service_order_id,
                'service_order_item_id' => $locked->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'reason' => $reason,
                'changed_by' => $changedBy,
            ]);

            $order = ServiceOrder::query()
                ->whereKey($locked->service_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            SyncOrderStatusFromItems::sync($order);

            return $locked;
        });
    }
}
