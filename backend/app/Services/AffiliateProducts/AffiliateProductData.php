<?php

namespace App\Services\AffiliateProducts;

final readonly class AffiliateProductData
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public string $provider,
        public string $externalId,
        public string $title,
        public string $url,
        public ?string $affiliateUrl,
        public ?int $priceCents,
        public string $currency,
        public ?string $imageUrl,
        public array $raw,
    ) {}
}
