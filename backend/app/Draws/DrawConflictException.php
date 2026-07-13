<?php

namespace App\Draws;

use App\Data\Api\V1\Shared\ErrorData;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class DrawConflictException extends RuntimeException implements ShouldntReport
{
    public function __construct(public readonly DrawFailureCode $failureCode)
    {
        parent::__construct($failureCode->value);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            (new ErrorData(message: __('draw.failures.'.$this->failureCode->value)))->toArray(),
            409,
        );
    }
}
