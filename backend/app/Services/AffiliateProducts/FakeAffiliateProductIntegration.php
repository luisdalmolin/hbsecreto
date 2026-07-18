<?php

namespace App\Services\AffiliateProducts;

use App\Exceptions\AffiliateProductCatalogUnavailable;

final class FakeAffiliateProductIntegration implements AffiliateProductCatalog
{
    /** @var list<AffiliateProductData> */
    private array $results = [];

    /** @var list<array{query: string, limit: int}> */
    private array $searches = [];

    private bool $unavailable = false;

    public function search(string $query, int $limit): array
    {
        $this->searches[] = ['query' => $query, 'limit' => $limit];

        if ($this->unavailable) {
            throw new AffiliateProductCatalogUnavailable;
        }

        return array_slice($this->results, 0, $limit);
    }

    public function replaceResults(AffiliateProductData ...$results): void
    {
        $this->results = array_values($results);
    }

    /** @return list<array{query: string, limit: int}> */
    public function searches(): array
    {
        return $this->searches;
    }

    public function simulateUnavailable(): void
    {
        $this->unavailable = true;
    }
}
