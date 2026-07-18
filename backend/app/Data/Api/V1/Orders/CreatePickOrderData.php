<?php

namespace App\Data\Api\V1\Orders;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'CreatePickOrderRequest', required: ['receiverParticipantId'])]
final class CreatePickOrderData extends Data
{
    public function __construct(
        #[IntegerType, Min(1)]
        #[OA\Property(minimum: 1, example: 2)]
        public int $receiverParticipantId,
    ) {}
}
