<?php

use App\Enums\EditionStatus;
use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    app()->setLocale('pt_BR');
    $this->admin = User::factory()->create();
    $this->group = Group::factory()->create(['created_by' => $this->admin->id]);
    $this->adminMember = GroupMember::factory()->for($this->group)->active($this->admin)->admin()->create();
    Sanctum::actingAs($this->admin);
});

test('an admin creates a placeholder and invitation tokens are hashed and rotated', function (): void {
    $memberId = $this->postJson("/api/v1/groups/{$this->group->id}/members", [
        'displayName' => 'João',
        'email' => 'joao@example.com',
    ])->assertCreated()->json('id');

    $firstToken = $this->postJson("/api/v1/groups/{$this->group->id}/members/{$memberId}/invite")
        ->assertCreated()
        ->assertJsonMissingPath('member.inviteToken')
        ->json('inviteToken');

    $member = GroupMember::query()->findOrFail($memberId);
    expect($firstToken)->toBeString()
        ->and($member->invite_token)->toBe(hash('sha256', $firstToken))
        ->and($member->invite_token)->not->toBe($firstToken);

    $secondToken = $this->postJson("/api/v1/groups/{$this->group->id}/members/{$memberId}/invite")
        ->assertCreated()
        ->json('inviteToken');

    expect($secondToken)->not->toBe($firstToken)
        ->and($member->refresh()->invite_token)->toBe(hash('sha256', $secondToken));
});

test('a matching account previews a redacted invitation and claims it only once', function (): void {
    $member = GroupMember::factory()->for($this->group)->create([
        'display_name' => 'João',
        'email' => 'JOAO@example.com',
    ]);
    $token = $this->postJson("/api/v1/groups/{$this->group->id}/members/{$member->id}/invite")
        ->assertCreated()
        ->json('inviteToken');

    auth()->forgetGuards();
    $this->getJson("/api/v1/invitations/{$token}")
        ->assertOk()
        ->assertJsonPath('groupName', $this->group->name)
        ->assertJsonPath('displayName', 'João')
        ->assertJsonMissingPath('email')
        ->assertJsonMissingPath('inviteToken');

    $invitee = User::factory()->create(['email' => 'joao@EXAMPLE.com']);
    Sanctum::actingAs($invitee);
    $this->postJson("/api/v1/invitations/{$token}/claim")
        ->assertCreated()
        ->assertJsonPath('userId', $invitee->id)
        ->assertJsonPath('status', 'active');

    expect($member->refresh()->invite_token)->toBeNull()
        ->and($member->invite_expires_at)->toBeNull()
        ->and($member->joined_at)->not->toBeNull();

    $this->postJson("/api/v1/invitations/{$token}/claim")->assertNotFound();
});

test('invalid expired and claimed invitation tokens share the same not found response', function (): void {
    $member = GroupMember::factory()->for($this->group)->create([
        'invite_token' => hash('sha256', 'expired-token'),
        'invite_expires_at' => now()->subMinute(),
    ]);

    $invalid = $this->getJson('/api/v1/invitations/invalid-token')->assertNotFound()->json('message');
    $expired = $this->getJson('/api/v1/invitations/expired-token')->assertNotFound()->json('message');
    $member->update(['user_id' => User::factory()->create()->id, 'invite_expires_at' => now()->addDay()]);
    $claimed = $this->getJson('/api/v1/invitations/expired-token')->assertNotFound()->json('message');

    expect($invalid)->toBe($expired)->and($expired)->toBe($claimed);
});

test('claim enforces the optional email and never merges an existing membership', function (): void {
    $invitee = User::factory()->create(['email' => 'other@example.com']);
    $member = GroupMember::factory()->for($this->group)->create(['email' => 'expected@example.com']);
    $token = $this->postJson("/api/v1/groups/{$this->group->id}/members/{$member->id}/invite")->assertCreated()->json('inviteToken');
    Sanctum::actingAs($invitee);

    $this->postJson("/api/v1/invitations/{$token}/claim")
        ->assertConflict()
        ->assertJsonPath('message', 'Este convite foi emitido para outro endereço de e-mail.');
    expect($member->refresh()->user_id)->toBeNull();

    $member->update(['email' => null]);
    GroupMember::factory()->for($this->group)->active($invitee)->create();
    $this->postJson("/api/v1/invitations/{$token}/claim")
        ->assertConflict()
        ->assertJsonPath('message', 'Você já possui uma participação neste grupo.');
    expect($member->refresh()->user_id)->toBeNull();
});

test('the final claimed active admin cannot be demoted or deactivated', function (): void {
    $this->patchJson("/api/v1/groups/{$this->group->id}/members/{$this->adminMember->id}", ['role' => 'member'])
        ->assertConflict()
        ->assertJsonPath('message', 'O grupo precisa manter pelo menos um administrador ativo.');

    $this->putJson("/api/v1/groups/{$this->group->id}/members/{$this->adminMember->id}/deactivate")
        ->assertConflict();

    expect($this->adminMember->refresh()->role)->toBe(GroupMemberRole::Admin)
        ->and($this->adminMember->status)->toBe(GroupMemberStatus::Active);
});

test('editable rosters block leaving while drawn history does not', function (): void {
    $secondAdmin = User::factory()->create();
    GroupMember::factory()->for($this->group)->active($secondAdmin)->admin()->create();
    $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
    EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->adminMember->id,
    ]);

    $this->putJson("/api/v1/groups/{$this->group->id}/members/{$this->adminMember->id}/deactivate")
        ->assertConflict()
        ->assertJsonPath('message', 'O participante precisa ser removido das edições em preparação antes de sair do grupo.');

    $edition->update(['status' => EditionStatus::Drawn, 'drawn_at' => now()]);
    $this->putJson("/api/v1/groups/{$this->group->id}/members/{$this->adminMember->id}/deactivate")
        ->assertOk()
        ->assertJsonPath('status', 'inactive');
});

test('members may update their own display name but cannot manage group fields', function (): void {
    $memberUser = User::factory()->create();
    $member = GroupMember::factory()->for($this->group)->active($memberUser)->create();
    $other = GroupMember::factory()->for($this->group)->active()->create();
    Sanctum::actingAs($memberUser);

    $this->patchJson("/api/v1/groups/{$this->group->id}/members/{$member->id}", ['displayName' => 'Novo apelido'])
        ->assertOk()
        ->assertJsonPath('displayName', 'Novo apelido');

    $this->patchJson("/api/v1/groups/{$this->group->id}/members/{$member->id}", ['role' => 'admin'])
        ->assertForbidden();
    $this->patchJson("/api/v1/groups/{$this->group->id}/members/{$other->id}", ['displayName' => 'Não permitido'])
        ->assertForbidden();
    $this->patchJson("/api/v1/groups/{$this->group->id}", ['name' => 'Não permitido'])
        ->assertForbidden();
});

test('inactive members lose nested group access without revealing the group', function (): void {
    $inactiveUser = User::factory()->create();
    GroupMember::factory()->for($this->group)->active($inactiveUser)->inactive()->create();
    Sanctum::actingAs($inactiveUser);

    $this->getJson("/api/v1/groups/{$this->group->id}/members")->assertNotFound();
    $this->getJson("/api/v1/groups/{$this->group->id}/editions")->assertNotFound();
});

test('member identity fields are visible only to self and active administrators', function (): void {
    $memberUser = User::factory()->create();
    $member = GroupMember::factory()->for($this->group)->active($memberUser)->create(['email' => 'member@example.com']);
    $otherUser = User::factory()->create();
    $other = GroupMember::factory()->for($this->group)->active($otherUser)->create(['email' => 'other@example.com']);
    Sanctum::actingAs($memberUser);

    $this->getJson("/api/v1/groups/{$this->group->id}/members")
        ->assertOk()
        ->assertJsonFragment(['id' => $member->id, 'userId' => $memberUser->id, 'email' => 'member@example.com'])
        ->assertJsonFragment(['id' => $other->id, 'userId' => null, 'email' => null]);

    Sanctum::actingAs($this->admin);
    $this->getJson("/api/v1/groups/{$this->group->id}/members")
        ->assertOk()
        ->assertJsonFragment(['id' => $member->id, 'userId' => $memberUser->id, 'email' => 'member@example.com'])
        ->assertJsonFragment(['id' => $other->id, 'userId' => $otherUser->id, 'email' => 'other@example.com']);
});

test('reactivation restores claimed members to active and placeholders to invited', function (): void {
    $claimed = GroupMember::factory()->for($this->group)->active()->inactive()->create();
    $placeholder = GroupMember::factory()->for($this->group)->inactive()->create();

    $this->putJson("/api/v1/groups/{$this->group->id}/members/{$claimed->id}/reactivate")
        ->assertOk()
        ->assertJsonPath('status', 'active');
    $this->putJson("/api/v1/groups/{$this->group->id}/members/{$placeholder->id}/reactivate")
        ->assertOk()
        ->assertJsonPath('status', 'invited');

    expect($claimed->refresh()->status)->toBe(GroupMemberStatus::Active)
        ->and($placeholder->refresh()->status)->toBe(GroupMemberStatus::Invited);
});

test('claiming an invitation requires authentication', function (): void {
    $member = GroupMember::factory()->for($this->group)->create();
    $token = $this->postJson("/api/v1/groups/{$this->group->id}/members/{$member->id}/invite")
        ->assertCreated()
        ->json('inviteToken');
    auth()->forgetGuards();

    $this->postJson("/api/v1/invitations/{$token}/claim")->assertUnauthorized();
});

test('invitation previews are throttled', function (): void {
    $member = GroupMember::factory()->for($this->group)->create();
    $token = $this->postJson("/api/v1/groups/{$this->group->id}/members/{$member->id}/invite")
        ->assertCreated()
        ->json('inviteToken');
    auth()->forgetGuards();

    foreach (range(1, 10) as $attempt) {
        $this->getJson("/api/v1/invitations/{$token}")->assertOk();
    }

    $this->getJson("/api/v1/invitations/{$token}")->assertTooManyRequests();
});
