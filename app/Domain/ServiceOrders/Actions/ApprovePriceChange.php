<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Exceptions\DomainConflictException;
use App\Models\ServiceOrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Menyetujui perubahan harga item (order-state-machine: waiting_approval →
 * approved; ADR D2, business-rules §1 snapshot).
 *
 * - unit_price item di-snapshot ke harga final yang disetujui.
 * - subtotal item dihitung ulang; total order dihitung ulang dari seluruh item.
 * - price_approved_by / price_approved_at diisi.
 * - Only saat order berstatus waiting_approval.
 */
final class ApprovePriceChange
{
    public static function approve(
        ServiceOrderItem $item,
        int $approvedPrice,
        ?string $reason = null,
        ?int $approvedBy = null,
    ): ServiceOrderItem {
        return DB::transaction(function () use ($item, $approvedPrice, $reason, $approvedBy) {
            $lockedItem = ServiceOrderItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $order = $lockedItem->serviceOrder()->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::WaitingApproval) {
                throw new DomainConflictException(
                    'Persetujuan harga hanya berlaku saat order menunggu approval.',
                );
            }

            if ($approvedPrice < 0) {
                throw new DomainConflictException('Harga tidak boleh negatif.');
            }

            // Snapshot harga final + audit.
            $lockedItem->unit_price = $approvedPrice;
            $lockedItem->proposed_price = null;
            $lockedItem->subtotal = $approvedPrice * $lockedItem->quantity;
            $lockedItem->price_approved_by = $approvedBy;
            $lockedItem->price_approved_at = now();
            $lockedItem->save();

            // Hitung ulang total order dari seluruh item.
            $itemRows = $order->items()
                ->get()
                ->map(fn (ServiceOrderItem $i) => ['subtotal' => $i->subtotal])
                ->all();
            CalculateOrderTotals::recalculate($order, $itemRows);
            $order->save();

            TransitionOrderStatus::transition(
                $order,
                OrderStatus::Approved,
                reason: $reason ?? 'Perubahan harga disetujui.',
                changedBy: $approvedBy,
            );

            return $lockedItem->refresh();
        });
    }
}
