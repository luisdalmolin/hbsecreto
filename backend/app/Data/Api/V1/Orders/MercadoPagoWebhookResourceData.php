<?php

namespace App\Data\Api\V1\Orders;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'MercadoPagoWebhookResource', required: ['id'])]
final class MercadoPagoWebhookResourceData extends Data
{
    public function __construct(
        #[OA\Property(type: 'string')]
        public string $id,
    ) {}
}
