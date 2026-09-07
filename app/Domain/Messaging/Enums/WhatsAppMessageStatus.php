<?php

namespace App\Domain\Messaging\Enums;

enum WhatsAppMessageStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
}
