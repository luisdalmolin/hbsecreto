<?php

namespace App\Data\Api\V1\Products;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;

final class SearchProductsData extends Data
{
    public function __construct(
        #[Min(2), Max(100), Regex('/\S/u')]
        public string $q,
        #[IntegerType, Min(1), Max(20)]
        public int $limit = 20,
    ) {}
}
