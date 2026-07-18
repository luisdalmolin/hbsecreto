<?php

namespace App\Services\Payments;

use Carbon\CarbonImmutable;

final readonly class PaymentRecordData
{
    /** @param array<string, mixed> $providerPayload */
    public function __construct(
        public string $providerReference,
        public string $externalReference,
        public string $status,
        public ?string $statusDetail,
        public int $amountCents,
        public string $currency,
        public ?CarbonImmutable $paidAt,
        public array $providerPayload,
    ) {}
}
