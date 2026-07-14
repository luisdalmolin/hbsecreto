<?php

namespace App\Data\Api\V1\Wishes;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'ReorderWishesRequest', required: ['wishIds'])]
final class ReorderWishesData extends Data
{
    /** @param list<int> $wishIds */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(type: 'integer', minimum: 1), uniqueItems: true)] public array $wishIds,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'wishIds' => ['required', 'array', 'list'],
            'wishIds.*' => ['required', 'integer', 'distinct', 'min:1'],
        ];
    }
}
