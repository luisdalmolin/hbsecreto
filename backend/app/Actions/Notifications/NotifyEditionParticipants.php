<?php

namespace App\Actions\Notifications;

use App\Enums\GroupMemberStatus;
use App\Models\Edition;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

final class NotifyEditionParticipants
{
    public function handle(Edition $edition, AppNotification $notification): void
    {
        User::query()
            ->whereHas('groupMemberships', fn ($members) => $members
                ->where('group_id', $edition->group_id)
                ->where('status', GroupMemberStatus::Active)
                ->whereHas('editionParticipants', fn ($participants) => $participants
                    ->where('edition_id', $edition->id)))
            ->orderBy('id')
            ->chunkById(200, fn ($users) => Notification::send($users, $notification));
    }
}
