<?php

use App\Enums\EditionStatus;
use App\Models\Assignment;
use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ConversationMessageNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    app()->setLocale('pt_BR');
    Carbon::setTestNow('2026-12-01 12:00:00');
    $this->admin = User::factory()->create(['name' => 'Administrador']);
    $this->owner = User::factory()->create(['name' => 'Ana']);
    $this->giver = User::factory()->create(['name' => 'Bruno']);
    $this->receiver = User::factory()->create(['name' => 'Carla']);
    $this->group = Group::factory()->create(['created_by' => $this->admin->id]);
    GroupMember::factory()->for($this->group)->active($this->admin)->admin()->create();
    $this->ownerMember = GroupMember::factory()->for($this->group)->active($this->owner)->create(['display_name' => 'Ana']);
    $this->giverMember = GroupMember::factory()->for($this->group)->active($this->giver)->create(['display_name' => 'Bruno']);
    $this->receiverMember = GroupMember::factory()->for($this->group)->active($this->receiver)->create(['display_name' => 'Carla']);
    $this->edition = Edition::factory()->for($this->group)->drawn()->create(['created_by' => $this->admin->id]);
    $this->ownerParticipant = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->ownerMember->id,
    ]);
    $this->giverParticipant = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->giverMember->id,
    ]);
    $this->receiverParticipant = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->receiverMember->id,
    ]);

    $incomingAssignment = Assignment::factory()->create([
        'edition_id' => $this->edition->id,
        'giver_edition_participant_id' => $this->giverParticipant->id,
        'receiver_edition_participant_id' => $this->ownerParticipant->id,
    ]);
    $outgoingAssignment = Assignment::factory()->create([
        'edition_id' => $this->edition->id,
        'giver_edition_participant_id' => $this->ownerParticipant->id,
        'receiver_edition_participant_id' => $this->receiverParticipant->id,
    ]);
    Assignment::factory()->create([
        'edition_id' => $this->edition->id,
        'giver_edition_participant_id' => $this->receiverParticipant->id,
        'receiver_edition_participant_id' => $this->giverParticipant->id,
    ]);
    $this->incoming = Conversation::factory()->create([
        'edition_id' => $this->edition->id,
        'assignment_id' => $incomingAssignment->id,
    ]);
    $this->outgoing = Conversation::factory()->create([
        'edition_id' => $this->edition->id,
        'assignment_id' => $outgoingAssignment->id,
    ]);
    $this->editionConversation = Conversation::factory()->edition()->create([
        'edition_id' => $this->edition->id,
    ]);
    $this->incomingMessage = Message::factory()->create([
        'conversation_id' => $this->incoming->id,
        'sender_edition_participant_id' => $this->giverParticipant->id,
        'body' => 'Você prefere azul ou verde?',
    ]);
    Message::factory()->create([
        'conversation_id' => $this->incoming->id,
        'sender_edition_participant_id' => $this->ownerParticipant->id,
        'body' => 'Prefiro verde.',
    ]);
    $this->url = "/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/conversations";
    Sanctum::actingAs($this->owner);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('participants see edition and assignment conversations without giver identity leakage', function (): void {
    $response = $this->getJson($this->url)
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.type', 'edition')
        ->assertJsonPath('data.0.role', 'member')
        ->assertJsonPath('data.0.counterpart', null)
        ->assertJsonPath('data.1.role', 'receiver')
        ->assertJsonPath('data.1.counterpart.anonymous', true)
        ->assertJsonPath('data.1.counterpart.displayName', null)
        ->assertJsonPath('data.1.unreadCount', 1)
        ->assertJsonPath('data.2.role', 'giver')
        ->assertJsonPath('data.2.counterpart.anonymous', false)
        ->assertJsonPath('data.2.counterpart.displayName', 'Carla');

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))->not->toContain('Bruno');
});

test('assignment messages expose only an anonymous author before reveal', function (): void {
    $response = $this->getJson("{$this->url}/{$this->incoming->id}/messages")
        ->assertOk()
        ->assertJsonCount(2, 'messages')
        ->assertJsonPath('messages.0.id', $this->incomingMessage->id)
        ->assertJsonPath('messages.0.isMine', false)
        ->assertJsonPath('messages.0.author.anonymous', true)
        ->assertJsonPath('messages.0.author.displayName', null)
        ->assertJsonPath('messages.1.isMine', true)
        ->assertJsonMissingPath('messages.0.senderId');

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))->not->toContain('Bruno');
});

test('both sides can send trimmed messages', function (): void {
    $created = $this->postJson("{$this->url}/{$this->incoming->id}/messages", [
        'body' => '  Tamanho M, por favor.  ',
    ])->assertCreated()
        ->assertJsonPath('body', 'Tamanho M, por favor.')
        ->assertJsonPath('isMine', true);

    expect(Message::query()->findOrFail($created->json('id'))->sender_edition_participant_id)
        ->toBe($this->ownerParticipant->id);

    Sanctum::actingAs($this->giver);
    $this->postJson("{$this->url}/{$this->incoming->id}/messages", ['body' => 'Combinado!'])
        ->assertCreated()
        ->assertJsonPath('isMine', true);
});

test('edition chat is available before the draw and identifies message authors', function (): void {
    $this->edition->update(['status' => EditionStatus::Open, 'drawn_at' => null]);

    Sanctum::actingAs($this->giver);
    $created = $this->postJson("{$this->url}/{$this->editionConversation->id}/messages", [
        'body' => '  O encontro continua às oito?  ',
    ])->assertCreated()
        ->assertJsonPath('body', 'O encontro continua às oito?')
        ->assertJsonPath('author.anonymous', false)
        ->assertJsonPath('author.displayName', 'Bruno')
        ->json('id');

    Sanctum::actingAs($this->owner);
    $this->getJson("{$this->url}/{$this->editionConversation->id}/messages")
        ->assertOk()
        ->assertJsonPath('conversation.type', 'edition')
        ->assertJsonPath('conversation.role', 'member')
        ->assertJsonPath('conversation.canSend', true)
        ->assertJsonPath('messages.0.id', $created)
        ->assertJsonPath('messages.0.author.displayName', 'Bruno')
        ->assertJsonPath('messages.0.author.anonymous', false);
});

test('a message notifies only the counterpart without leaking message content', function (): void {
    Notification::fake();

    $this->postJson("{$this->url}/{$this->incoming->id}/messages", [
        'body' => 'Isto deve permanecer privado.',
    ])->assertCreated();

    Notification::assertSentTo(
        $this->giver,
        ConversationMessageNotification::class,
        function (ConversationMessageNotification $notification): bool {
            $payload = $notification->toDatabase($this->giver);

            return $notification->conversationId === $this->incoming->id
                && $payload['body'] === 'Você recebeu uma nova mensagem anônima no amigo secreto.'
                && ! str_contains($payload['body'], 'privado');
        },
    );
    Notification::assertNotSentTo($this->receiver, ConversationMessageNotification::class);
});

test('an edition message notifies the other active participants', function (): void {
    Notification::fake();

    $this->postJson("{$this->url}/{$this->editionConversation->id}/messages", [
        'body' => 'Mensagem para todo o grupo.',
    ])->assertCreated();

    Notification::assertSentTo([$this->giver, $this->receiver], ConversationMessageNotification::class);
    Notification::assertNotSentTo([$this->owner, $this->admin], ConversationMessageNotification::class);
});

test('message bodies require non-whitespace text within the length limit', function (): void {
    $endpoint = "{$this->url}/{$this->incoming->id}/messages";

    $this->withoutRequestValidation()->postJson($endpoint, ['body' => '   '])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
    $this->withoutRequestValidation()->postJson($endpoint, ['body' => str_repeat('a', 1001)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});

test('read tracking clears unread messages and counts only newer counterpart messages', function (): void {
    $this->putJson("{$this->url}/{$this->incoming->id}/read", [
        'messageId' => $this->incoming->messages()->latest('id')->value('id'),
    ])->assertNoContent();
    $this->getJson($this->url)->assertJsonPath('data.1.unreadCount', 0);

    Carbon::setTestNow('2026-12-01 12:01:00');
    Message::factory()->create([
        'conversation_id' => $this->incoming->id,
        'sender_edition_participant_id' => $this->giverParticipant->id,
        'body' => 'Mais uma pergunta.',
    ]);
    Message::factory()->create([
        'conversation_id' => $this->incoming->id,
        'sender_edition_participant_id' => $this->ownerParticipant->id,
        'body' => 'Minha resposta não conta como não lida.',
    ]);

    $this->getJson($this->url)->assertJsonPath('data.1.unreadCount', 1);
    expect(ConversationRead::query()->whereBelongsTo($this->incoming)->whereBelongsTo($this->ownerParticipant, 'editionParticipant')->exists())->toBeTrue();
});

test('read tracking never consumes a message that arrived after the rendered thread', function (): void {
    $lastRenderedMessageId = $this->incoming->messages()->latest('id')->value('id');
    Carbon::setTestNow('2026-12-01 12:01:00');
    Message::factory()->create([
        'conversation_id' => $this->incoming->id,
        'sender_edition_participant_id' => $this->giverParticipant->id,
        'body' => 'Cheguei depois da tela ser carregada.',
    ]);

    $this->putJson("{$this->url}/{$this->incoming->id}/read", [
        'messageId' => $lastRenderedMessageId,
    ])->assertNoContent();

    $this->getJson($this->url)->assertJsonPath('data.1.unreadCount', 1);
});

test('read tracking never moves the cursor backwards for a stale client', function (): void {
    Carbon::setTestNow('2026-12-01 12:01:00');
    $newerMessage = Message::factory()->create([
        'conversation_id' => $this->incoming->id,
        'sender_edition_participant_id' => $this->giverParticipant->id,
        'body' => 'Mensagem mais recente.',
    ]);

    $endpoint = "{$this->url}/{$this->incoming->id}/read";
    $this->putJson($endpoint, ['messageId' => $newerMessage->id])->assertNoContent();
    $this->putJson($endpoint, ['messageId' => $this->incomingMessage->id])->assertNoContent();

    $read = ConversationRead::query()
        ->whereBelongsTo($this->incoming)
        ->whereBelongsTo($this->ownerParticipant, 'editionParticipant')
        ->firstOrFail();

    expect($read->last_read_at->equalTo($newerMessage->created_at))->toBeTrue();
    $this->getJson($this->url)->assertJsonPath('data.1.unreadCount', 0);
});

test('reveal unmasks the giver while archived conversations remain read only', function (): void {
    $this->edition->update(['status' => EditionStatus::Revealed, 'revealed_at' => now()]);
    $this->getJson($this->url)
        ->assertJsonPath('data.1.counterpart.anonymous', false)
        ->assertJsonPath('data.1.counterpart.displayName', 'Bruno')
        ->assertJsonPath('data.1.canSend', true);

    $this->edition->update(['status' => EditionStatus::Archived]);
    $this->getJson("{$this->url}/{$this->incoming->id}/messages")
        ->assertOk()
        ->assertJsonPath('conversation.canSend', false);
    $this->postJson("{$this->url}/{$this->incoming->id}/messages", ['body' => 'Mensagem tardia'])
        ->assertConflict()
        ->assertJsonPath('message', 'Não é possível enviar mensagens em uma edição arquivada.');
});

test('conversation access enforces participation and edition scoped binding', function (): void {
    Sanctum::actingAs($this->admin);
    $this->getJson($this->url)->assertForbidden();
    $this->getJson("{$this->url}/{$this->incoming->id}/messages")->assertForbidden();
    $this->postJson("{$this->url}/{$this->incoming->id}/messages", ['body' => 'Tentativa'])->assertForbidden();

    Sanctum::actingAs($this->owner);
    $otherEdition = Edition::factory()->for($this->group)->drawn()->create(['created_by' => $this->admin->id]);
    $otherConversation = Conversation::factory()->create(['edition_id' => $otherEdition->id]);
    $this->getJson("{$this->url}/{$otherConversation->id}/messages")->assertNotFound();

    $this->ownerMember->update(['status' => 'inactive']);
    $this->getJson($this->url)->assertNotFound();
    $this->getJson("{$this->url}/{$this->incoming->id}/messages")->assertNotFound();
});
