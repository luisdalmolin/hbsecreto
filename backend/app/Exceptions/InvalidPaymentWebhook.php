<?php

namespace App\Exceptions;

use App\Data\Api\V1\Shared\ErrorData;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class InvalidPaymentWebhook extends RuntimeException implements ShouldntReport
{
    public function render(): JsonResponse
    {
        return response()->json(
            (new ErrorData(message: __('orders.invalid_webhook')))->toArray(),
            401,
        );
    }
}
