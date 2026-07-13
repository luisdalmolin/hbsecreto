<?php

namespace App\Data\Api\V1\Editions;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

#[OA\Schema(schema: 'UpdateEditionRequest')]
final class UpdateEditionData extends Data
{
    /** @param array<string, mixed>|Optional $settings */
    public function __construct(
        #[Max(255)] #[OA\Property(type: 'string', maxLength: 255)] public string|Optional $name,
        #[IntegerType, Min(0)] #[OA\Property(type: 'integer', nullable: true, minimum: 0)] public int|Optional|null $budgetCents,
        #[Date] #[OA\Property(type: 'string', format: 'date', nullable: true)] public string|Optional|null $eventDate,
        #[OA\Property(type: 'object')] public array|Optional $settings,
    ) {}
}
