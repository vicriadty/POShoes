<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceOrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Assignment item layanan ke teknisi.
 *
 * - Teknisi diwakili role 'teknisi' (WorkItem assignee).
 * - Item hanya boleh di-assign bila masih bisa dikerjakan (bukan completed).
 * - Order harus dalam status yang mengizinkan produksi (approved ke atas);
 *   draft/received/inspection belum waktunya.
 */
final class AssignItemToTechnician
{
    public static function assign(ServiceOrderItem $item, User $technician, ?int $assignedBy = null): ServiceOrderItem
    {
        if (! $technician->hasRole('teknisi')) {
            throw new DomainConflictException('Assignee harus berperan teknisi.');
        }

        return DB::transaction(function () use ($item, $technician, $assignedBy) {
            $locked = ServiceOrderItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status->value === 'completed' || $locked->status->value === 'cancelled') {
                throw new DomainConflictException('Item sudah selesai/dibatalkan, tidak dapat di-assign.');
            }

            $order = ServiceOrder::query()
                ->whereKey($locked->service_order_id)
                ->lockForUpdate()
                ->firstOrFail();
            $orderStatus = $order->status->value;
            $allowed = ['approved', 'in_progress', 'quality_check', 'waiting_approval'];

            if (! in_array($orderStatus, $allowed, true)) {
                throw new DomainConflictException(
                    "Order berstatus '{$orderStatus}' — belum waktunya meng-assign teknisi.",
                );
            }

            $locked->assigned_to = $technician->id;
            $locked->save();

            // Catat assignment di riwayat status item (dari → ke sama tidak ditulis;
            // gunakan riwayat dengan catatan singkat via status history yang informatif).
            ServiceOrderStatusHistory::create([
                'service_order_id' => $locked->service_order_id,
                'service_order_item_id' => $locked->id,
                'from_status' => $locked->status->value,
                'to_status' => $locked->status->value,
                'reason' => 'Assigned to teknisi #'.$technician->id,
                'changed_by' => $assignedBy ?? $technician->id,
            ]);

            return $locked;
        });
    }
}
