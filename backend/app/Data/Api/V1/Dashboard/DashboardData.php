<?php

namespace App\Data\Api\V1\Dashboard;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'Dashboard',
    required: ['featuredEdition', 'groups'],
    properties: [
        new OA\Property(property: 'featuredEdition', oneOf: [new OA\Schema(ref: '#/components/schemas/DashboardEdition'), new OA\Schema(type: 'null')]),
    ],
)]
final class DashboardData extends Resource
{
    /** @param DataCollection<int, DashboardGroupData> $groups */
    public function __construct(
        public ?DashboardEditionData $featuredEdition,
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/DashboardGroup'))]
        public DataCollection $groups,
    ) {}
}
