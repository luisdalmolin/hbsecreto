<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Draws\PerformDraw;
use App\Actions\Draws\PreflightDraw;
use App\Data\Api\V1\Draws\DrawPreflightData;
use App\Data\Api\V1\Draws\DrawReceiptData;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Group;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Draw')]
final class DrawController extends Controller
{
    #[Authorize('manageDraw', 'edition')]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/draw/preflight', operationId: 'preflightDraw', tags: ['Draw'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'A valid draw exists.', content: new OA\JsonContent(ref: '#/components/schemas/DrawPreflight')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The draw is invalid or impossible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function preflight(Group $group, Edition $edition, PreflightDraw $preflight): DrawPreflightData
    {
        $preflight->handle($edition);

        return new DrawPreflightData(true, $edition->participants()->count());
    }

    #[Authorize('manageDraw', 'edition')]
    #[OA\Post(
        path: '/api/v1/groups/{group}/editions/{edition}/draw', operationId: 'performDraw', tags: ['Draw'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Stable draw receipt without assignment mappings.', content: new OA\JsonContent(ref: '#/components/schemas/DrawReceipt')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The draw is invalid, impossible, or corrupt.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(Group $group, Edition $edition, PerformDraw $perform): DrawReceiptData
    {
        return DrawReceiptData::fromEdition($perform->handle($edition));
    }
}
