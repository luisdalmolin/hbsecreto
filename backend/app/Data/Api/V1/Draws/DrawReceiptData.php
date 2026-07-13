<?php

namespace App\Data\Api\V1\Draws;

use App\Enums\EditionStatus;
use App\Models\Edition;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'DrawReceipt', required: ['editionId', 'status', 'drawnAt', 'participantCount'])]
final class DrawReceiptData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $editionId,
        #[OA\Property(type: 'string', enum: ['drawn', 'revealed', 'archived'])] public EditionStatus $status,
        #[OA\Property(type: 'string', format: 'date-time')] public string $drawnAt,
        #[OA\Property(minimum: 2, example: 8)] public int $participantCount,
    ) {}

    public static function fromEdition(Edition $edition): self
    {
        if ($edition->drawn_at === null) {
            throw new \LogicException('A draw receipt requires a draw timestamp.');
        }

        return new self($edition->id, $edition->status, $edition->drawn_at->toIso8601String(), $edition->participants()->count());
    }
}
