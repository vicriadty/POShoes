<?php

namespace App\Domain\ServiceOrders\Enums;

enum ItemStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case WaitingMaterial = 'waiting_material';
    case QualityCheck = 'quality_check';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
