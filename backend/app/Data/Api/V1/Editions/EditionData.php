<?php

namespace App\Data\Api\V1\Editions;

use App\Enums\EditionStatus;
use App\Models\Edition;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'Edition',
    required: ['id', 'groupId', 'name', 'type', 'status', 'currency', 'settings', 'createdBy'],
    properties: [
        new OA\Property(property: 'eventDate', oneOf: [new OA\Schema(type: 'string', format: 'date'), new OA\Schema(type: 'null')]),
        new OA\Property(property: 'drawnAt', oneOf: [new OA\Schema(type: 'string', format: 'date-time'), new OA\Schema(type: 'null')]),
        new OA\Property(property: 'revealedAt', oneOf: [new OA\Schema(type: 'string', format: 'date-time'), new OA\Schema(type: 'null')]),
    ],
)]
final class EditionData extends Resource
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[MapInputName('group_id')] #[OA\Property(example: 1)] public int $groupId,
        #[OA\Property(example: 'Natal 2026')] public string $name,
        #[OA\Property(example: 'classic')] public string $type,
        #[OA\Property(type: 'string', enum: ['draft', 'open', 'drawn', 'revealed', 'archived'])] public EditionStatus $status,
        #[MapInputName('budget_cents')] #[OA\Property(nullable: true)] public ?int $budgetCents,
        #[OA\Property(example: 'BRL')] public string $currency,
        #[MapInputName('event_date')]
        public ?string $eventDate,
        #[OA\Property(type: 'object')] public array $settings,
        #[MapInputName('drawn_at')]
        public ?string $drawnAt,
        #[MapInputName('revealed_at')]
        public ?string $revealedAt,
        #[MapInputName('created_by')] #[OA\Property(example: 1)] public int $createdBy,
    ) {}

    public static function fromModel(Edition $edition): self
    {
        return new self(
            id: $edition->id,
            groupId: $edition->group_id,
            name: $edition->name,
            type: $edition->type,
            status: $edition->status,
            budgetCents: $edition->budget_cents,
            currency: $edition->currency,
            eventDate: $edition->event_date?->toDateString(),
            settings: $edition->settings,
            drawnAt: $edition->drawn_at?->toIso8601String(),
            revealedAt: $edition->revealed_at?->toIso8601String(),
            createdBy: $edition->created_by,
        );
    }
}
