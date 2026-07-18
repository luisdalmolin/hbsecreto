<?php

use App\Enums\ConversationType;
use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Models\Conversation;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Notifications\EditionRevealedNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    app()->setLocale('pt_BR');
    $this->admin = User::factory()->create();
    $this->group = Group::factory()->create(['created_by' => $this->admin->id]);
    $this->adminMember = GroupMember::factory()->for($this->group)->active($this->admin)->admin()->create();
    Sanctum::actingAs($this->admin);
});

test('edition creation uses launch defaults and seeds only active members', function (): void {
    $activeMember = GroupMember::factory()->for($this->group)->active()->create();
    $invitedMember = GroupMember::factory()->for($this->group)->create();

    $editionId = $this->postJson("/api/v1/groups/{$this->group->id}/editions", [
        'name' => 'Natal 2026',
        'budgetCents' => 15000,
        'eventDate' => '2026-12-24',
    ])->assertCreated()
        ->assertJsonPath('type', 'classic')
        ->assertJsonPath('status', 'draft')
        ->assertJsonPath('currency', 'BRL')
        ->assertJsonPath('eventDate', '2026-12-24')
        ->json('id');

    $edition = Edition::query()->findOrFail($editionId);
    expect($edition->participants()->pluck('group_member_id')->all())
        ->toContain($this->adminMember->id, $activeMember->id)
        ->not->toContain($invitedMember->id)
        ->and($edition->conversations()->count())->toBe(1)
        ->and($edition->conversations()->firstOrFail()->type)->toBe(ConversationType::Edition);
});

test('edition and participant lists are scoped to the selected tenant', function (): void {
    $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
    $otherGroup = Group::factory()->create();
    $hiddenEdition = Edition::factory()->for($otherGroup)->create();
    $participant = EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->adminMember->id,
    ]);

    $this->getJson("/api/v1/groups/{$this->group->id}/editions")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $edition->id)
        ->assertJsonMissing(['id' => $hiddenEdition->id]);

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $participant->id)
        ->assertJsonPath('currentParticipantId', $participant->id);

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$hiddenEdition->id}")->assertNotFound();
});

test('the current participant identifier is independent of the roster page', function (): void {
    $viewer = User::factory()->create();
    $viewerMember = GroupMember::factory()->for($this->group)->active($viewer)->create();
    $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);

    foreach (range(1, 15) as $position) {
        $member = GroupMember::factory()->for($this->group)->active()->create();
        EditionParticipant::factory()->for($edition)->create([
            'group_id' => $this->group->id,
            'group_member_id' => $member->id,
            'created_at' => now()->subMinutes(30 - $position),
        ]);
    }

    $viewerParticipant = EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $viewerMember->id,
        'created_at' => now(),
    ]);
    Sanctum::actingAs($viewer);

    $response = $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants")
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('currentParticipantId', $viewerParticipant->id)
        ->assertJsonPath('meta.currentPage', 1)
        ->assertJsonPath('meta.lastPage', 2);

    expect($response->json('data.*.id'))->not->toContain($viewerParticipant->id);
});

test('an invited placeholder can join and two participants can open an edition', function (): void {
    $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
    EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->adminMember->id,
    ]);
    $placeholder = GroupMember::factory()->for($this->group)->create();

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants", [
        'groupMemberId' => $placeholder->id,
    ])->assertCreated()->assertJsonPath('groupMember.id', $placeholder->id);

    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/open")
        ->assertOk()
        ->assertJsonPath('status', 'open');
});

test('opening rejects fewer than two participants and lifecycle is strictly forward', function (): void {
    $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
    EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->adminMember->id,
    ]);

    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/open")
        ->assertConflict()
        ->assertJsonPath('message', 'A edição precisa ter pelo menos dois participantes.');
    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/reveal")->assertConflict();

    $edition->update(['status' => EditionStatus::Drawn, 'drawn_at' => now()]);
    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/reveal")
        ->assertOk()
        ->assertJsonPath('status', 'revealed');
    expect($edition->refresh()->revealed_at)->not->toBeNull();

    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/archive")
        ->assertOk()
        ->assertJsonPath('status', 'archived');
    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/archive")->assertConflict();
});

test('revealing an edition notifies every active claimed participant', function (): void {
    Notification::fake();
    $member = User::factory()->create();
    $memberRecord = GroupMember::factory()->for($this->group)->active($member)->create();
    $edition = Edition::factory()->for($this->group)->drawn()->create(['created_by' => $this->admin->id]);
    EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->adminMember->id,
    ]);
    EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $memberRecord->id,
    ]);

    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/reveal")->assertOk();

    Notification::assertSentTo([$this->admin, $member], EditionRevealedNotification::class);
    Notification::assertSentTimes(EditionRevealedNotification::class, 2);
});

test('the roster freezes after draw and only drafts can be deleted', function (): void {
    $edition = Edition::factory()->for($this->group)->drawn()->create(['created_by' => $this->admin->id]);
    $member = GroupMember::factory()->for($this->group)->active()->create();

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants", [
        'groupMemberId' => $member->id,
    ])->assertConflict()->assertJsonPath('message', 'A lista de participantes não pode mais ser alterada.');

    $this->deleteJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}")->assertConflict();

    $draft = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
    $this->deleteJson("/api/v1/groups/{$this->group->id}/editions/{$draft->id}")->assertNoContent();
    $this->assertModelMissing($draft);
});

test('an open edition cannot drop below two participants while a draft can', function (): void {
    $secondMember = GroupMember::factory()->for($this->group)->active()->create();
    $edition = Edition::factory()->for($this->group)->open()->create(['created_by' => $this->admin->id]);
    $firstParticipant = EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->adminMember->id,
    ]);
    EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $secondMember->id,
    ]);

    $this->deleteJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants/{$firstParticipant->id}")
        ->assertConflict()
        ->assertJsonPath('message', 'Uma edição aberta precisa manter pelo menos dois participantes.');
    expect($edition->participants()->count())->toBe(2);

    $edition->update(['status' => EditionStatus::Draft]);
    $this->deleteJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants/{$firstParticipant->id}")
        ->assertNoContent();
    expect($edition->participants()->count())->toBe(1);
});

test('participants on either side of pending or paid purchase constraints cannot be removed', function (): void {
    foreach ([OrderStatus::Pending, OrderStatus::Paid] as $status) {
        foreach (['giver', 'receiver'] as $side) {
            $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
            $targetMember = GroupMember::factory()->for($this->group)->active()->create();
            $counterpartMember = GroupMember::factory()->for($this->group)->active()->create();
            $target = EditionParticipant::factory()->for($edition)->create([
                'group_id' => $this->group->id,
                'group_member_id' => $targetMember->id,
            ]);
            $counterpart = EditionParticipant::factory()->for($edition)->create([
                'group_id' => $this->group->id,
                'group_member_id' => $counterpartMember->id,
            ]);
            $order = Order::factory()->for($this->admin)->for($edition)->create(['status' => $status]);
            $constraint = DrawConstraint::query()->create([
                'edition_id' => $edition->id,
                'type' => DrawConstraintType::MustPair,
                'giver_edition_participant_id' => $side === 'giver' ? $target->id : $counterpart->id,
                'receiver_edition_participant_id' => $side === 'receiver' ? $target->id : $counterpart->id,
                'source' => DrawConstraintSource::Purchase,
                'order_id' => $order->id,
                'created_by' => null,
            ]);

            $this->deleteJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants/{$target->id}")
                ->assertConflict()
                ->assertJsonPath('message', 'Este participante está vinculado a uma escolha comprada com pagamento pendente ou confirmado.');
            $this->assertModelExists($target);
            $this->assertModelExists($order);
            $this->assertModelExists($constraint);
        }
    }
});

test('a participant with edition chat history gets a localized conflict instead of a database error', function (): void {
    $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
    $member = GroupMember::factory()->for($this->group)->active()->create();
    $participant = EditionParticipant::factory()->for($edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $member->id,
    ]);
    $conversation = Conversation::factory()->for($edition)->edition()->create();
    $message = Message::factory()->for($conversation)->create([
        'edition_id' => $edition->id,
        'sender_edition_participant_id' => $participant->id,
    ]);

    $this->deleteJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants/{$participant->id}")
        ->assertConflict()
        ->assertJsonPath('message', 'Este participante não pode ser removido porque já enviou mensagens nesta edição.');
    $this->assertModelExists($participant);
    $this->assertModelExists($message);
});

test('inactive and cross-group members cannot be added to a roster', function (): void {
    $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
    $inactiveMember = GroupMember::factory()->for($this->group)->inactive()->create();
    $otherGroupMember = GroupMember::factory()->create();

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants", [
        'groupMemberId' => $inactiveMember->id,
    ])->assertConflict()->assertJsonPath('message', 'Este participante não está disponível para esta edição.');

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/participants", [
        'groupMemberId' => $otherGroupMember->id,
    ])->assertNotFound();
});

test('drawn editions reject updates and serialize lifecycle timestamps as date-time strings', function (): void {
    $edition = Edition::factory()->for($this->group)->drawn()->create([
        'created_by' => $this->admin->id,
        'event_date' => '2026-12-24',
    ]);

    $this->patchJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}", ['name' => 'Alteração tardia'])
        ->assertConflict()
        ->assertJsonPath('message', 'A edição não pode mais ser alterada.');

    $response = $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/reveal")
        ->assertOk()
        ->assertJsonPath('eventDate', '2026-12-24');

    expect($response->json('drawnAt'))->toBeString()->toContain('T')
        ->and($response->json('revealedAt'))->toBeString()->toContain('T');
});

test('ordinary members cannot perform representative edition administration', function (): void {
    $memberUser = User::factory()->create();
    GroupMember::factory()->for($this->group)->active($memberUser)->create();
    $edition = Edition::factory()->for($this->group)->create(['created_by' => $this->admin->id]);
    Sanctum::actingAs($memberUser);

    $this->postJson("/api/v1/groups/{$this->group->id}/editions", ['name' => 'Não permitida'])
        ->assertForbidden();
    $this->putJson("/api/v1/groups/{$this->group->id}/editions/{$edition->id}/open")
        ->assertForbidden();
});
