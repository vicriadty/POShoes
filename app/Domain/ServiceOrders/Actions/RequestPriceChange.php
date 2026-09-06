<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Exceptions\DomainConflictException;
use App\Models\ServiceOrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Mengajukan perubahan harga pada satu item (order-state-machine: transisi
 * inspection → waiting_approval; ADR D2).
 *
 * - Harga yang diusulkan disimpan di `proposed_price` (item), bukan ditimpa ke
 *   `unit_price` — unit_price tetap estimasi sampai disetujui.
 * - Hanya berlaku saat order berstatus inspection.
 * - Setelah ini, order bergerak ke waiting_approval.
 */
final class RequestPriceChange
{
    public static function request(
        ServiceOrderItem $item,
        int $proposedPrice,
        ?string $reason = null,
        ?int $changedBy = null,
    ): ServiceOrderItem {
        return DB::transaction(function () use ($item, $proposedPrice, $reason, $changedBy) {
            $lockedItem = ServiceOrderItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $order = $lockedItem->serviceOrder()->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::Inspection) {
                throw new DomainConflictException(
                    'Perubahan harga hanya dapat diajukan saat order dalam inspeksi.',
                );
            }

            if ($proposedPrice < 0) {
                throw new DomainConflictException('Harga tidak boleh negatif.');
            }

            $lockedItem->proposed_price = $proposedPrice;
            $lockedItem->save();

            TransitionOrderStatus::transition(
                $order,
                OrderStatus::WaitingApproval,
                reason: $reason ?? 'Perubahan harga diajukan.',
                changedBy: $changedBy,
            );

            return $lockedItem->refresh();
        });
    }
}
