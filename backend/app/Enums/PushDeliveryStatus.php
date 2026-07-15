<?php

namespace App\Enums;

enum PushDeliveryStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Expired = 'expired';
}
