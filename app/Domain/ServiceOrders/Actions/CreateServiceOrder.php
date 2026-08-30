<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Domain\ServiceOrders\Enums\ItemStatus;
use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Customer;
use App\Models\ServiceCatalog;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ShoeItem;
use Illuminate\Support\Facades\DB;

/**
 * Penerimaan order (Order Intake, Phase 3).
 *
 * Membuat service_order + items + shoes + pivot order_item_shoes dalam satu
 * database transaction. Harga layanan di-snapshot dari master (business-rules §1,
 * ADR D2). Total dihitung server-side (api-convention: jangan percaya frontend).
 *
 * @phpstan-type ShoeInput array{brand?: string|null, model?: string|null, color?: string|null, size?: string|null, material?: string|null, condition_summary?: string|null, customer_description?: string|null}
 * @phpstan-type ItemInput array{service_catalog_id: int, quantity?: int, notes?: string|null, shoe_ids?: array<int>}
 */
final class CreateServiceOrder
{
    /**
     * @param  ItemInput[]  $items
     * @param  ShoeInput[]  $shoes
     */
    public static function create(
        Customer $customer,
        int $branchId,
        int $receivedBy,
        array $items,
        array $shoes = [],
        ?string $customerNotes = null,
        ?string $internalNotes = null,
        ?\DateTimeInterface $estimatedCompletedAt = null,
        int $discountAmount = 0,
        int $taxAmount = 0,
    ): ServiceOrder {
        if (count($items) === 0) {
            throw new DomainConflictException('Order wajib memiliki minimal satu item layanan.');
        }

        return DB::transaction(function () use (
            $customer, $branchId, $receivedBy, $items, $shoes,
            $customerNotes, $internalNotes, $estimatedCompletedAt, $discountAmount, $taxAmount,
        ): ServiceOrder {
            $order = ServiceOrder::create([
                'order_number' => GenerateOrderNumber::generate(),
                'customer_id' => $customer->id,
                'branch_id' => $branchId,
                'received_by' => $receivedBy,
                'status' => OrderStatus::Draft,
                'estimated_completed_at' => $estimatedCompletedAt,
                'customer_notes' => $customerNotes,
                'internal_notes' => $internalNotes,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
            ]);

            $shoeMap = [];
            foreach ($shoes as $shoeData) {
                $shoe = ShoeItem::create([
                    'service_order_id' => $order->id,
                    'brand' => $shoeData['brand'] ?? null,
                    'model' => $shoeData['model'] ?? null,
                    'color' => $shoeData['color'] ?? null,
                    'size' => $shoeData['size'] ?? null,
                    'material' => $shoeData['material'] ?? null,
                    'condition_summary' => $shoeData['condition_summary'] ?? null,
                    'customer_description' => $shoeData['customer_description'] ?? null,
                ]);
                $shoeMap[$shoe->id] = $shoe->id;
            }

            $itemRows = [];
            foreach ($items as $itemData) {
                $catalog = ServiceCatalog::query()->findOrFail((int) $itemData['service_catalog_id']);
                if (! $catalog->active) {
                    throw new DomainConflictException(
                        "Layanan '{$catalog->name}' tidak aktif.",
                    );
                }

                $quantity = max(1, (int) ($itemData['quantity'] ?? 1));
                $unitPrice = (int) $catalog->base_price;

                $orderItem = ServiceOrderItem::create([
                    'service_order_id' => $order->id,
                    'service_catalog_id' => $catalog->id,
                    'service_name' => $catalog->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'subtotal' => $unitPrice * $quantity,
                    'estimated_duration_minutes' => $catalog->estimated_duration_minutes,
                    'status' => ItemStatus::Pending,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Pivot order_item_shoes (ADR D1: 1 order = banyak sepatu, banyak layanan).
                foreach ($itemData['shoe_ids'] ?? [] as $shoeId) {
                    $orderItem->shoes()->attach($shoeId, ['quantity' => 1]);
                }

                $itemRows[] = ['subtotal' => $orderItem->subtotal];
            }

            CalculateOrderTotals::recalculate($order, $itemRows, $discountAmount, $taxAmount);
            $order->save();

            return $order->load(['customer', 'items', 'shoes', 'statusHistories']);
        });
    }
}
