<?php

namespace App\Data\Api\V1\Editions;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'CreateEditionRequest', required: ['name'])]
final class CreateEditionData extends Data
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        #[Max(255)] #[OA\Property(maxLength: 255, example: 'Natal 2026')] public string $name,
        #[IntegerType, Min(0)] #[OA\Property(nullable: true, minimum: 0)] public ?int $budgetCents = null,
        #[Date] #[OA\Property(format: 'date', nullable: true)] public ?string $eventDate = null,
        #[OA\Property(type: 'object')] public array $settings = [],
    ) {}
}
