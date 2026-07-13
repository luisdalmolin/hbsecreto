<?php

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    app()->setLocale('pt_BR');
});

test('an authenticated user creates a group and becomes its active admin atomically', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/groups', [
        'name' => 'Família Silva',
        'description' => 'Natal em família',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('name', 'Família Silva')
        ->assertJsonPath('createdBy', $user->id);

    $group = Group::query()->sole();
    $member = GroupMember::query()->sole();

    expect($member->group_id)->toBe($group->id)
        ->and($member->user_id)->toBe($user->id)
        ->and($member->role)->toBe(GroupMemberRole::Admin)
        ->and($member->status)->toBe(GroupMemberStatus::Active)
        ->and($member->joined_at)->not->toBeNull();
});

test('group list is tenant scoped before filtering and uses a camel case pagination envelope', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $visibleGroup = Group::factory()->create(['name' => 'Visível']);
    $hiddenGroup = Group::factory()->create(['name' => 'Visível também']);
    GroupMember::factory()->for($visibleGroup)->active($user)->create();
    GroupMember::factory()->for($hiddenGroup)->active($otherUser)->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/groups?filter[name]=Vis')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $visibleGroup->id)
        ->assertJsonMissing(['id' => $hiddenGroup->id])
        ->assertJsonStructure(['data', 'meta' => ['currentPage', 'lastPage', 'perPage', 'total']]);
});

test('a nonmember cannot discover a group', function (): void {
    $group = Group::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/groups/{$group->id}")->assertNotFound();
});
