<?php

namespace App\Services\Payments;

final readonly class PaymentCheckoutData
{
    /** @param array<string, mixed> $providerPayload */
    public function __construct(
        public string $providerReference,
        public string $checkoutUrl,
        public array $providerPayload,
    ) {}
}
