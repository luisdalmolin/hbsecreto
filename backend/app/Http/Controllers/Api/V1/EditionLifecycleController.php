<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Editions\TransitionEdition;
use App\Data\Api\V1\Editions\EditionData;
use App\Enums\EditionStatus;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Group;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Edition lifecycle')]
final class EditionLifecycleController extends Controller
{
    #[Authorize('transition', 'edition')]
    #[OA\Put(
        path: '/api/v1/groups/{group}/editions/{edition}/open', operationId: 'openEdition', tags: ['Edition lifecycle'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Edition opened.', content: new OA\JsonContent(ref: '#/components/schemas/Edition')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The lifecycle transition is invalid or the roster has fewer than two participants.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function open(Group $group, Edition $edition, TransitionEdition $transitionEdition): EditionData
    {
        return EditionData::from($transitionEdition->handle($edition, EditionStatus::Open));
    }

    #[Authorize('transition', 'edition')]
    #[OA\Put(
        path: '/api/v1/groups/{group}/editions/{edition}/reveal', operationId: 'revealEdition', tags: ['Edition lifecycle'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Drawn edition revealed.', content: new OA\JsonContent(ref: '#/components/schemas/Edition')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Only a drawn edition can be revealed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function reveal(Group $group, Edition $edition, TransitionEdition $transitionEdition): EditionData
    {
        return EditionData::from($transitionEdition->handle($edition, EditionStatus::Revealed));
    }

    #[Authorize('transition', 'edition')]
    #[OA\Put(
        path: '/api/v1/groups/{group}/editions/{edition}/archive', operationId: 'archiveEdition', tags: ['Edition lifecycle'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Revealed edition archived.', content: new OA\JsonContent(ref: '#/components/schemas/Edition')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Only a revealed edition can be archived.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function archive(Group $group, Edition $edition, TransitionEdition $transitionEdition): EditionData
    {
        return EditionData::from($transitionEdition->handle($edition, EditionStatus::Archived));
    }
}
