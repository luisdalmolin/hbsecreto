<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Groups\ClaimInvitation;
use App\Actions\Groups\FindInvitation;
use App\Data\Api\V1\Groups\GroupMemberData;
use App\Data\Api\V1\Groups\InvitationPreviewData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Invitations')]
final class InvitationController extends Controller
{
    #[OA\Get(
        path: '/api/v1/invitations/{token}', operationId: 'previewInvitation', tags: ['Invitations'],
        parameters: [new OA\PathParameter(name: 'token', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'A redacted invitation preview.', content: new OA\JsonContent(ref: '#/components/schemas/InvitationPreview')),
            new OA\Response(response: 404, description: 'The invitation is invalid, expired, or already claimed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 429, description: 'Too many invitation attempts.'),
        ],
    )]
    public function show(string $token, FindInvitation $findInvitation): InvitationPreviewData
    {
        $invitation = $findInvitation->handle($token);
        $member = $invitation->member;
        $group = $member->group()->firstOrFail();

        return new InvitationPreviewData(
            groupName: $group->name,
            displayName: $member->display_name,
            expiresAt: $invitation->expiresAt->toIso8601String(),
        );
    }

    #[OA\Post(
        path: '/api/v1/invitations/{token}/claim', operationId: 'claimInvitation', tags: ['Invitations'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'token', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 201, description: 'Invitation claimed.', content: new OA\JsonContent(ref: '#/components/schemas/GroupMember')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The invitation is invalid, expired, or already claimed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The invitation cannot be claimed by this account.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 429, description: 'Too many invitation attempts.'),
        ],
    )]
    public function claim(string $token, Request $request, ClaimInvitation $claimInvitation): GroupMemberData
    {
        /** @var User $user */
        $user = $request->user();

        return GroupMemberData::forViewer($claimInvitation->handle($token, $user), $user, viewerIsAdmin: false);
    }
}
