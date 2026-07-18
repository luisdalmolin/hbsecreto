<?php

use App\Models\Assignment;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Wish;
use Laravel\Sanctum\Sanctum;

test('dashboard requires authentication and returns an empty state for a new user', function (): void {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('featuredEdition', null)
        ->assertJsonCount(0, 'groups');
});

test('dashboard consolidates the three newest visible group summaries', function (): void {
    $user = User::factory()->create();
    $groups = collect(range(1, 4))->map(function (int $position) use ($user): Group {
        $group = Group::factory()->create([
            'name' => "Grupo {$position}",
            'created_at' => now()->subDays(5 - $position),
        ]);
        GroupMember::factory()->for($group)->active($user)->create();
        GroupMember::factory()->for($group)->active()->create();

        return $group;
    });
    $newestGroup = $groups->last();
    $currentEdition = Edition::factory()->for($newestGroup)->open()->create();
    Edition::factory()->for($newestGroup)->archived()->create();

    $hiddenGroup = Group::factory()->create(['name' => 'Grupo oculto']);
    GroupMember::factory()->for($hiddenGroup)->active()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonCount(3, 'groups')
        ->assertJsonPath('groups.0.id', $newestGroup->id)
        ->assertJsonPath('groups.0.memberCount', 2)
        ->assertJsonPath('groups.0.currentEditionId', $currentEdition->id)
        ->assertJsonPath('groups.0.currentEditionStatus', 'open')
        ->assertJsonMissing(['id' => $groups->first()->id])
        ->assertJsonMissing(['id' => $hiddenGroup->id]);

    expect($response->json('featuredEdition'))->toBeNull();
});

test('dashboard features the most actionable edition without leaking the assignment', function (): void {
    $user = User::factory()->create();

    $openGroup = Group::factory()->create(['name' => 'Grupo aberto']);
    GroupMember::factory()->for($openGroup)->active($user)->admin()->create();
    Edition::factory()->for($openGroup)->open()->create([
        'name' => 'Natal futuro',
        'event_date' => now()->addDay(),
        'created_by' => $user->id,
    ]);

    $drawnGroup = Group::factory()->create(['name' => 'Família']);
    $membership = GroupMember::factory()->for($drawnGroup)->active($user)->admin()->create();
    $drawnEdition = Edition::factory()->for($drawnGroup)->drawn()->create([
        'name' => 'Natal principal',
        'budget_cents' => 15000,
        'event_date' => now()->addWeek(),
        'created_by' => $user->id,
    ]);
    $participant = EditionParticipant::factory()->for($drawnEdition)->create([
        'group_id' => $drawnGroup->id,
        'group_member_id' => $membership->id,
    ]);
    $receiver = EditionParticipant::factory()->for($drawnEdition)->create([
        'group_id' => $drawnGroup->id,
        'group_member_id' => GroupMember::factory()->for($drawnGroup)->active()->create()->id,
    ]);
    Assignment::factory()->for($drawnEdition)->create([
        'giver_edition_participant_id' => $participant->id,
        'receiver_edition_participant_id' => $receiver->id,
    ]);
    Wish::factory()->for($participant)->create();

    $archivedGroup = Group::factory()->create(['name' => 'Grupo arquivado']);
    $archivedMembership = GroupMember::factory()->for($archivedGroup)->active($user)->admin()->create();
    $archivedEdition = Edition::factory()->for($archivedGroup)->archived()->create(['created_by' => $user->id]);
    EditionParticipant::factory()->for($archivedEdition)->create([
        'group_id' => $archivedGroup->id,
        'group_member_id' => $archivedMembership->id,
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('featuredEdition.groupId', $drawnGroup->id)
        ->assertJsonPath('featuredEdition.groupName', 'Família')
        ->assertJsonPath('featuredEdition.editionId', $drawnEdition->id)
        ->assertJsonPath('featuredEdition.editionName', 'Natal principal')
        ->assertJsonPath('featuredEdition.status', 'drawn')
        ->assertJsonPath('featuredEdition.budgetCents', 15000)
        ->assertJsonPath('featuredEdition.isAdmin', true)
        ->assertJsonPath('featuredEdition.isParticipant', true)
        ->assertJsonPath('featuredEdition.participantCount', 2)
        ->assertJsonPath('featuredEdition.wishCount', 1)
        ->assertJsonPath('featuredEdition.assignmentAvailable', true)
        ->assertJsonMissingPath('featuredEdition.receiver')
        ->assertJsonMissingPath('featuredEdition.assignment')
        ->assertJsonMissing(['displayName' => $receiver->groupMember()->firstOrFail()->display_name]);
});
