<?php

namespace App\Domain\ServiceOrders\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Received = 'received';
    case Inspection = 'inspection';
    case WaitingApproval = 'waiting_approval';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case QualityCheck = 'quality_check';
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';
    case Cancelled = 'cancelled';

    /**
     * Status non-terminal yang masih menerima pembaruan.
     */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::PickedUp, self::Cancelled], true);
    }
}
