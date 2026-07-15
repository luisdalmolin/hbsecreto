<?php

namespace App\Models;

use App\Enums\AppNotificationType;
use App\Enums\PushDeliveryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $notification_id
 * @property AppNotificationType $notification_type
 * @property int|null $push_device_id
 * @property string $expo_push_token_hash
 * @property string|null $expo_ticket_id
 * @property PushDeliveryStatus $status
 * @property string|null $error_code
 * @property string|null $error_message
 * @property Carbon|null $attempted_at
 * @property Carbon|null $receipt_checked_at
 * @property Carbon|null $completed_at
 */
#[Fillable([
    'notification_id',
    'notification_type',
    'push_device_id',
    'expo_push_token_hash',
    'expo_ticket_id',
    'status',
    'error_code',
    'error_message',
    'attempted_at',
    'receipt_checked_at',
    'completed_at',
])]
class PushDelivery extends Model
{
    /** @return BelongsTo<PushDevice, $this> */
    public function pushDevice(): BelongsTo
    {
        return $this->belongsTo(PushDevice::class);
    }

    protected function casts(): array
    {
        return [
            'notification_type' => AppNotificationType::class,
            'status' => PushDeliveryStatus::class,
            'attempted_at' => 'datetime',
            'receipt_checked_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
