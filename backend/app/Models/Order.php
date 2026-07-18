<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Policies\OrderPolicy;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $edition_id
 * @property OrderType $type
 * @property int $amount_cents
 * @property string $currency
 * @property OrderStatus $status
 * @property string $payment_provider
 * @property string|null $provider_reference
 * @property string|null $checkout_idempotency_key
 * @property Carbon|null $checkout_expires_at
 * @property Carbon|null $paid_at
 * @property array<string, mixed> $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DrawConstraint|null $drawConstraint
 */
#[Fillable(['user_id', 'edition_id', 'type', 'amount_cents', 'currency', 'status', 'payment_provider', 'provider_reference', 'checkout_idempotency_key', 'checkout_expires_at', 'paid_at', 'metadata'])]
#[UsePolicy(OrderPolicy::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $attributes = [
        'type' => OrderType::PickPurchase->value,
        'currency' => 'BRL',
        'status' => OrderStatus::Pending->value,
        'metadata' => '{}',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return HasOne<DrawConstraint, $this> */
    public function drawConstraint(): HasOne
    {
        return $this->hasOne(DrawConstraint::class);
    }

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'amount_cents' => 'integer',
            'status' => OrderStatus::class,
            'checkout_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
