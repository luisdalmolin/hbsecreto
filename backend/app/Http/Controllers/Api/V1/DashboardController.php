<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Api\V1\Dashboard\DashboardData;
use App\Data\Api\V1\Dashboard\DashboardEditionData;
use App\Data\Api\V1\Dashboard\DashboardGroupData;
use App\Enums\EditionStatus;
use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Dashboard')]
final class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/dashboard',
        operationId: 'getDashboard',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'The authenticated user home dashboard.', content: new OA\JsonContent(ref: '#/components/schemas/Dashboard')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function __invoke(Request $request): DashboardData
    {
        /** @var User $user */
        $user = $request->user();
        $groups = QueryBuilder::for(
            Group::query()
                ->visibleTo($user)
                ->withCount(['members as active_member_count' => fn (Builder $members): Builder => $members->where('status', GroupMemberStatus::Active)])
                ->with('currentEdition')
                ->latest()
                ->orderByDesc('id'),
        )
            ->allowedFilters()
            ->allowedIncludes()
            ->allowedSorts()
            ->allowedFields()
            ->limit(3)
            ->get();
        $groupItems = $groups->map(fn (Group $group): DashboardGroupData => new DashboardGroupData(
            id: $group->id,
            name: $group->name,
            memberCount: $group->active_member_count ?? 0,
            currentEditionId: $group->currentEdition?->id,
            currentEditionStatus: $group->currentEdition?->status,
        ));
        /** @var DataCollection<int, DashboardGroupData> $groupData */
        $groupData = DashboardGroupData::collect($groupItems, DataCollection::class);

        return new DashboardData(
            featuredEdition: $this->featuredEdition($user),
            groups: $groupData,
        );
    }

    private function featuredEdition(User $user): ?DashboardEditionData
    {
        $edition = Edition::query()
            ->where('status', '!=', EditionStatus::Archived)
            ->whereHas('group.members', fn (Builder $members): Builder => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->where(function (Builder $editions) use ($user): void {
                $editions
                    ->whereHas('participants.groupMember', fn (Builder $members): Builder => $members
                        ->whereBelongsTo($user)
                        ->where('status', GroupMemberStatus::Active))
                    ->orWhereHas('group.members', fn (Builder $members): Builder => $members
                        ->whereBelongsTo($user)
                        ->where('status', GroupMemberStatus::Active)
                        ->where('role', GroupMemberRole::Admin));
            })
            ->orderByRaw(
                'case status when ? then 0 when ? then 1 when ? then 2 when ? then 3 else 4 end',
                [EditionStatus::Drawn->value, EditionStatus::Open->value, EditionStatus::Draft->value, EditionStatus::Revealed->value],
            )
            ->orderByRaw('case when event_date is null then 1 else 0 end')
            ->orderBy('event_date')
            ->orderByDesc('id')
            ->first();

        if ($edition === null) {
            return null;
        }

        $group = $edition->group()->firstOrFail();
        $membership = $group->members()
            ->whereBelongsTo($user)
            ->active()
            ->firstOrFail();
        $participant = $edition->participants()
            ->whereHas('groupMember', fn (Builder $members): Builder => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->first();

        return $this->featuredEditionData($edition, $group, $membership, $participant);
    }

    private function featuredEditionData(Edition $edition, Group $group, GroupMember $membership, ?EditionParticipant $participant): DashboardEditionData
    {
        return new DashboardEditionData(
            groupId: $edition->group_id,
            groupName: $group->name,
            editionId: $edition->id,
            editionName: $edition->name,
            status: $edition->status,
            budgetCents: $edition->budget_cents,
            currency: $edition->currency,
            eventDate: $edition->event_date?->toDateString(),
            isAdmin: $membership->role === GroupMemberRole::Admin,
            isParticipant: $participant !== null,
            participantCount: $edition->participants()->count(),
            wishCount: $participant?->wishes()->count() ?? 0,
            assignmentAvailable: $participant?->givenAssignments()->exists() ?? false,
        );
    }
}
