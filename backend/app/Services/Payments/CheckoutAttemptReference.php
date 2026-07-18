<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

final readonly class CheckoutAttemptReference
{
    private const PREFIX = 'pick-order:';

    public function __construct(
        public int $orderId,
        public string $attemptId,
    ) {
        if ($orderId < 1 || ! Str::isUuid($attemptId)) {
            throw new \InvalidArgumentException('The checkout attempt reference is invalid.');
        }
    }

    public static function forOrder(Order $order): self
    {
        if ($order->checkout_idempotency_key === null) {
            throw new \LogicException('A checkout idempotency key is required.');
        }

        return new self($order->id, $order->checkout_idempotency_key);
    }

    public static function parse(string $reference): ?self
    {
        if (! preg_match('/\A'.preg_quote(self::PREFIX, '/').'(\d+):([0-9a-f-]{36})\z/i', $reference, $matches)) {
            return null;
        }

        try {
            return new self((int) $matches[1], $matches[2]);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function toString(): string
    {
        return self::PREFIX.$this->orderId.':'.$this->attemptId;
    }
}
