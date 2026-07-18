<?php

namespace App\Services\AffiliateProducts;

interface AffiliateProductCatalog
{
    /** @return list<AffiliateProductData> */
    public function search(string $query, int $limit): array;
}
