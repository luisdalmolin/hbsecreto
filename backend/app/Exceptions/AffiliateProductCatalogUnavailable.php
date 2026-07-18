<?php

namespace App\Exceptions;

use App\Data\Api\V1\Shared\ErrorData;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

final class AffiliateProductCatalogUnavailable extends RuntimeException implements ShouldntReport
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('products.catalog_unavailable', previous: $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            (new ErrorData(message: __('products.catalog_unavailable')))->toArray(),
            502,
        );
    }
}
