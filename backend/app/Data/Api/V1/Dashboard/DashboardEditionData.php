<?php

namespace App\Data\Api\V1\Dashboard;

use App\Enums\EditionStatus;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'DashboardEdition',
    required: ['groupId', 'groupName', 'editionId', 'editionName', 'status', 'budgetCents', 'currency', 'eventDate', 'isAdmin', 'isParticipant', 'participantCount', 'wishCount', 'assignmentAvailable'],
    properties: [
        new OA\Property(property: 'budgetCents', oneOf: [new OA\Schema(type: 'integer', minimum: 0), new OA\Schema(type: 'null')]),
        new OA\Property(property: 'eventDate', oneOf: [new OA\Schema(type: 'string', format: 'date'), new OA\Schema(type: 'null')]),
    ],
)]
final class DashboardEditionData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $groupId,
        #[OA\Property(example: 'Família Silva')] public string $groupName,
        #[OA\Property(example: 4)] public int $editionId,
        #[OA\Property(example: 'Natal 2026')] public string $editionName,
        #[OA\Property(type: 'string', enum: ['draft', 'open', 'drawn', 'revealed'])] public EditionStatus $status,
        public ?int $budgetCents,
        #[OA\Property(example: 'BRL')] public string $currency,
        public ?string $eventDate,
        #[OA\Property(type: 'boolean')] public bool $isAdmin,
        #[OA\Property(type: 'boolean')] public bool $isParticipant,
        #[OA\Property(minimum: 0)] public int $participantCount,
        #[OA\Property(minimum: 0)] public int $wishCount,
        #[OA\Property(type: 'boolean')] public bool $assignmentAvailable,
    ) {}
}
