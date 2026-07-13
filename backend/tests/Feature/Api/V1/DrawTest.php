<?php

use App\Enums\EditionStatus;
use App\Models\Assignment;
use App\Models\DrawConstraint;
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
    $this->memberUsers = User::factory()->count(3)->create();
    $this->members = $this->memberUsers->map(fn (User $user): GroupMember => GroupMember::factory()->for($this->group)->active($user)->create());
    $this->edition = Edition::factory()->for($this->group)->open()->create(['created_by' => $this->admin->id]);
    $allMembers = collect([$this->adminMember])->merge($this->members);
    $this->participants = $allMembers->map(fn (GroupMember $member): EditionParticipant => EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $member->id,
    ]));
    Sanctum::actingAs($this->admin);
});

test('administrators can privately manage normalized constraints and preflight', function (): void {
    $response = $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_not_pair',
        'giverParticipantId' => $this->participants[1]->id,
        'receiverParticipantId' => $this->participants[0]->id,
    ])->assertCreated();

    $constraintId = $response->json('id');
    $response->assertJsonPath('giverParticipantId', $this->participants[0]->id)
        ->assertJsonPath('receiverParticipantId', $this->participants[1]->id)
        ->assertJsonPath('source', 'admin');

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints")
        ->assertOk()
        ->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw/preflight")
        ->assertOk()
        ->assertJson(['ready' => true, 'participantCount' => 4]);

    $this->deleteJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints/{$constraintId}")
        ->assertNoContent();
    expect(DrawConstraint::query()->count())->toBe(0);
});

test('constraint validation reports localized conflicts without leaking private rules', function (): void {
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->participants[0]->id,
        'receiverParticipantId' => $this->participants[1]->id,
    ])->assertCreated();

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->participants[0]->id,
        'receiverParticipantId' => $this->participants[2]->id,
    ])->assertConflict()->assertJsonPath('message', 'As regras do sorteio são conflitantes.');

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->participants[2]->id,
        'receiverParticipantId' => $this->participants[1]->id,
    ])->assertConflict()->assertJsonPath('message', 'As regras do sorteio são conflitantes.');

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_not_pair',
        'giverParticipantId' => $this->participants[1]->id,
        'receiverParticipantId' => $this->participants[0]->id,
    ])->assertConflict()->assertJsonPath('message', 'As regras do sorteio são conflitantes.');

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->participants[0]->id,
        'receiverParticipantId' => $this->participants[0]->id,
    ])->assertConflict()->assertJsonPath('message', 'Uma das regras do sorteio é inválida.');

    Sanctum::actingAs($this->memberUsers[0]);
    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints")
        ->assertForbidden()
        ->assertJsonMissing(['type' => 'must_pair']);
});

test('forced pairs coexist with unrelated exclusions regardless of normalized participant ordering', function (): void {
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->participants[0]->id,
        'receiverParticipantId' => $this->participants[1]->id,
    ])->assertCreated();
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_not_pair',
        'giverParticipantId' => $this->participants[0]->id,
        'receiverParticipantId' => $this->participants[2]->id,
    ])->assertCreated();

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->participants[3]->id,
        'receiverParticipantId' => $this->participants[0]->id,
    ])->assertCreated();
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_not_pair',
        'giverParticipantId' => $this->participants[3]->id,
        'receiverParticipantId' => $this->participants[1]->id,
    ])->assertCreated()
        ->assertJsonPath('giverParticipantId', $this->participants[1]->id)
        ->assertJsonPath('receiverParticipantId', $this->participants[3]->id);

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw/preflight")
        ->assertOk()
        ->assertJsonPath('ready', true);
});

test('preflight reports a Hall deficient rule set before drawing', function (): void {
    foreach ([[0, 1], [0, 2], [1, 2]] as [$giverIndex, $receiverIndex]) {
        $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
            'type' => 'must_not_pair',
            'giverParticipantId' => $this->participants[$giverIndex]->id,
            'receiverParticipantId' => $this->participants[$receiverIndex]->id,
        ])->assertCreated();
    }

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw/preflight")
        ->assertConflict()
        ->assertJsonPath('message', 'Não existe um sorteio válido com as regras atuais.');
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertConflict()
        ->assertJsonPath('message', 'Não existe um sorteio válido com as regras atuais.');
    expect(Assignment::query()->count())->toBe(0)
        ->and($this->edition->refresh()->status)->toBe(EditionStatus::Open)
        ->and($this->edition->drawn_at)->toBeNull();
});

test('draw is atomic, private, and sequentially idempotent', function (): void {
    $first = $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertCreated()
        ->assertJsonPath('status', 'drawn')
        ->assertJsonPath('participantCount', 4)
        ->assertJsonMissingPath('assignments')
        ->assertJsonMissingPath('seed')
        ->assertJsonMissingPath('penalty');

    expect(Assignment::query()->whereBelongsTo($this->edition)->count())->toBe(4)
        ->and($this->edition->refresh()->status)->toBe(EditionStatus::Drawn)
        ->and($this->edition->drawn_at)->not->toBeNull();

    $mapping = Assignment::query()->whereBelongsTo($this->edition)->orderBy('id')->get()->toArray();
    $second = $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertCreated();

    expect($second->json())->toBe($first->json())
        ->and(Assignment::query()->whereBelongsTo($this->edition)->orderBy('id')->get()->toArray())->toBe($mapping);
});

test('performed draw honors an administrator forced pair', function (): void {
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->participants[0]->id,
        'receiverParticipantId' => $this->participants[2]->id,
    ])->assertCreated();

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")->assertCreated();

    $assignment = Assignment::query()
        ->whereBelongsTo($this->edition)
        ->where('giver_edition_participant_id', $this->participants[0]->id)
        ->firstOrFail();

    expect($assignment->receiver_edition_participant_id)->toBe($this->participants[2]->id);
});

test('an open edition with a partial assignment set is never rerolled', function (): void {
    Assignment::query()->create([
        'edition_id' => $this->edition->id,
        'giver_edition_participant_id' => $this->participants[0]->id,
        'receiver_edition_participant_id' => $this->participants[1]->id,
    ]);

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertConflict()
        ->assertJsonPath('message', 'O resultado existente do sorteio está incompleto ou inconsistente.');
    expect(Assignment::query()->whereBelongsTo($this->edition)->count())->toBe(1)
        ->and($this->edition->refresh()->status)->toBe(EditionStatus::Open);
});

test('participants see only their receiver until the edition is revealed', function (): void {
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")->assertCreated();
    Sanctum::actingAs($this->memberUsers[0]);

    $mine = $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/my-assignment")
        ->assertOk()
        ->assertJsonStructure(['receiver' => ['participantId', 'groupMemberId', 'displayName']])
        ->assertJsonMissingPath('giver')
        ->assertJsonMissingPath('receiver.email')
        ->assertJsonMissingPath('receiver.userId');
    expect($mine->json('receiver.displayName'))->toBeString();

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/assignments")
        ->assertConflict()
        ->assertJsonPath('message', 'Os resultados completos ainda não foram revelados.');

    Sanctum::actingAs($this->admin);
    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/reveal")->assertOk();
    Sanctum::actingAs($this->memberUsers[0]);
    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/assignments")
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonStructure(['data' => [['giver' => ['participantId', 'groupMemberId', 'displayName'], 'receiver' => ['participantId', 'groupMemberId', 'displayName']]]])
        ->assertJsonMissingPath('data.0.giver.email');
});

test('assignment policies require an active claimed participant and enforce tenant isolation', function (): void {
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")->assertCreated();
    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/my-assignment")->assertNotFound();

    $this->members[0]->update(['status' => 'inactive']);
    Sanctum::actingAs($this->memberUsers[0]);
    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/my-assignment")->assertNotFound();
});

test('a drawn edition cannot be deleted or cascade its assignments', function (): void {
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")->assertCreated();
    $assignmentIds = Assignment::query()->whereBelongsTo($this->edition)->pluck('id')->all();

    $this->deleteJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}")
        ->assertConflict()
        ->assertJsonPath('message', 'Somente edições em rascunho podem ser excluídas.');

    expect($this->edition->fresh())->not->toBeNull()
        ->and(Assignment::query()->whereIn('id', $assignmentIds)->count())->toBe(count($assignmentIds));
});
