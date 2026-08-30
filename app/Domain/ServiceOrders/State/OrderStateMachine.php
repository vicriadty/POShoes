<?php

namespace App\Domain\ServiceOrders\State;

use App\Domain\ServiceOrders\Enums\OrderStatus;

/**
 * Matriks transisi status order (lihat docs/design/order-state-machine.md).
 *
 * Transisi yang tidak terdaftar dianggap invalid dan ditolak (409 DomainConflict).
 * Transisi non-empty di Phase 3: draft → received → inspection → approved/waiting_approval.
 * Aksi produksi (approved → in_progress → … → picked_up) dan pickup guard di Phase 5.
 */
final class OrderStateMachine
{
    /**
     * Map "from" → set "to" yang valid.
     *
     * @var array<string, array<int, OrderStatus>>
     */
    public const TRANSITIONS = [
        OrderStatus::Draft->value => [
            OrderStatus::Received,
            OrderStatus::Cancelled,
        ],
        OrderStatus::Received->value => [
            OrderStatus::Inspection,
            OrderStatus::Cancelled,
        ],
        OrderStatus::Inspection->value => [
            OrderStatus::Approved,
            OrderStatus::WaitingApproval,
            OrderStatus::Cancelled,
        ],
        OrderStatus::WaitingApproval->value => [
            OrderStatus::Approved,
            OrderStatus::Inspection,
            OrderStatus::Cancelled,
        ],
        OrderStatus::Approved->value => [
            OrderStatus::InProgress,
            OrderStatus::Cancelled,
        ],
        OrderStatus::InProgress->value => [
            OrderStatus::QualityCheck,
            OrderStatus::WaitingApproval,
            OrderStatus::Cancelled,
        ],
        OrderStatus::QualityCheck->value => [
            OrderStatus::InProgress,
            OrderStatus::ReadyForPickup,
            OrderStatus::Cancelled,
        ],
        OrderStatus::ReadyForPickup->value => [
            OrderStatus::PickedUp,
            OrderStatus::Cancelled,
        ],
        OrderStatus::PickedUp->value => [],
        OrderStatus::Cancelled->value => [],
    ];

    /**
     * @return OrderStatus[]
     */
    public static function allowedTransitions(OrderStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }

    public static function canTransition(OrderStatus $from, OrderStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, self::allowedTransitions($from), true);
    }
}
