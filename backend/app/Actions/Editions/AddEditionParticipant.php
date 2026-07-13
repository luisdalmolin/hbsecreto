<?php

namespace App\Actions\Editions;

use App\Enums\EditionStatus;
use App\Enums\GroupMemberStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\GroupMember;

final class AddEditionParticipant
{
    public function handle(Edition $edition, GroupMember $member): EditionParticipant
    {
        if (! in_array($edition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
            throw new DomainConflictException('editions.roster_frozen');
        }

        if ($member->group_id !== $edition->group_id || $member->status === GroupMemberStatus::Inactive) {
            throw new DomainConflictException('editions.member_unavailable');
        }

        if ($edition->participants()->whereBelongsTo($member, 'groupMember')->exists()) {
            throw new DomainConflictException('editions.participant_exists');
        }

        return $edition->participants()->create([
            'group_id' => $edition->group_id,
            'group_member_id' => $member->id,
        ]);
    }
}
