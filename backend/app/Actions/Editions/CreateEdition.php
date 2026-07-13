<?php

namespace App\Actions\Editions;

use App\Enums\EditionStatus;
use App\Enums\GroupMemberStatus;
use App\Models\Edition;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateEdition
{
    /** @param array<string, mixed> $settings */
    public function handle(
        Group $group,
        User $creator,
        string $name,
        ?int $budgetCents,
        ?string $eventDate,
        array $settings,
    ): Edition {
        return DB::transaction(function () use ($group, $creator, $name, $budgetCents, $eventDate, $settings): Edition {
            $edition = $group->editions()->create([
                'name' => $name,
                'type' => 'classic',
                'status' => EditionStatus::Draft,
                'budget_cents' => $budgetCents,
                'currency' => 'BRL',
                'event_date' => $eventDate,
                'settings' => $settings,
                'created_by' => $creator->id,
            ]);

            $participants = $group->members()
                ->where('status', GroupMemberStatus::Active)
                ->get(['id'])
                ->map(fn ($member): array => [
                    'group_id' => $group->id,
                    'group_member_id' => $member->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $edition->participants()->createMany($participants);

            return $edition;
        });
    }
}
