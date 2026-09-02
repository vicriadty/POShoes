<?php

namespace App\Domain\ServiceOrders\State;

use App\Domain\ServiceOrders\Enums\ItemStatus;

/**
 * Matriks transisi status item layanan (docs/design/order-state-machine.md).
 *
 * Transisi tidak terdaftar dianggap invalid → 409 DomainConflict.
 * Transisi Phase 5: pending → in_progress → waiting_material ↔ in_progress
 * → quality_check → completed, plus rework (quality_check → in_progress)
 * dan cancellable selama belum completed.
 */
final class ItemStateMachine
{
    /**
     * @var array<string, array<int, ItemStatus>>
     */
    public const TRANSITIONS = [
        ItemStatus::Pending->value => [
            ItemStatus::InProgress,
            ItemStatus::Cancelled,
        ],
        ItemStatus::InProgress->value => [
            ItemStatus::WaitingMaterial,
            ItemStatus::QualityCheck,
            ItemStatus::Cancelled,
        ],
        ItemStatus::WaitingMaterial->value => [
            ItemStatus::InProgress,
            ItemStatus::Cancelled,
        ],
        ItemStatus::QualityCheck->value => [
            ItemStatus::InProgress, // rework
            ItemStatus::Completed,
            ItemStatus::Cancelled,
        ],
        ItemStatus::Completed->value => [],
        ItemStatus::Cancelled->value => [],
    ];

    /**
     * @return ItemStatus[]
     */
    public static function allowedTransitions(ItemStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }

    public static function canTransition(ItemStatus $from, ItemStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, self::allowedTransitions($from), true);
    }
}
