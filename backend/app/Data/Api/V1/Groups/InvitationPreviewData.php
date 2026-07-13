<?php

namespace App\Data\Api\V1\Groups;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'InvitationPreview', required: ['groupName', 'displayName', 'expiresAt'])]
final class InvitationPreviewData extends Resource
{
    public function __construct(
        #[OA\Property(example: 'Família Silva')] public string $groupName,
        #[OA\Property(nullable: true, example: 'João')] public ?string $displayName,
        #[OA\Property(format: 'date-time')] public string $expiresAt,
    ) {}
}
