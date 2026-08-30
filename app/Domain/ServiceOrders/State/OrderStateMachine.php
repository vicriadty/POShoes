<?php

namespace App\Domain\ServiceOrders\State;

use App\Domain\ServiceOrders\Enums\OrderStatus;

/**
 * Matriks transisi status order (lihat docs/design/order-state-machine.md).
 *
 * Transisi yang tidak terdaftar dianggap invalid dan ditolak (409 DomainConflict).
 * Transisi Phase 3–4: draft → received → inspection → approved/waiting_approval,
 * hingga ready_for_pickup → picked_up (guard lunas, ADR D4). Aksi produksi
 * (approved → in_progress → … → ready_for_pickup) di Phase 5.
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
