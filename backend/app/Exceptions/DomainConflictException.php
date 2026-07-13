<?php

namespace App\Exceptions;

use App\Data\Api\V1\Shared\ErrorData;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class DomainConflictException extends RuntimeException implements ShouldntReport
{
    public function __construct(private readonly string $translationKey)
    {
        parent::__construct($translationKey);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            (new ErrorData(message: __($this->translationKey)))->toArray(),
            409,
        );
    }
}
