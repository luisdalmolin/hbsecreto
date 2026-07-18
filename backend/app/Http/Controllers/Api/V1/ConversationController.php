<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Conversations\MarkConversationRead;
use App\Actions\Conversations\SendMessage;
use App\Data\Api\V1\Conversations\ConversationCollectionData;
use App\Data\Api\V1\Conversations\ConversationData;
use App\Data\Api\V1\Conversations\ConversationThreadData;
use App\Data\Api\V1\Conversations\CreateMessageData;
use App\Data\Api\V1\Conversations\MarkConversationReadData;
use App\Data\Api\V1\Conversations\MessageData;
use App\Enums\ConversationType;
use App\Enums\GroupMemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Conversations')]
final class ConversationController extends Controller
{
    #[Authorize('viewAny', [Conversation::class, 'edition'])]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/conversations', operationId: 'listConversations', tags: ['Conversations'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Edition and assignment conversations visible to the authenticated participant.', content: new OA\JsonContent(ref: '#/components/schemas/ConversationCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active edition participant is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Group $group, Edition $edition, Request $request): ConversationCollectionData
    {
        $participant = $this->participantFor($edition, $request);
        $conversationTable = (new Conversation)->getTable();
        $messageTable = (new Message)->getTable();
        $readTable = (new ConversationRead)->getTable();
        $readExists = ConversationRead::query()
            ->selectRaw('1')
            ->whereColumn("{$readTable}.conversation_id", "{$conversationTable}.id")
            ->where('edition_participant_id', $participant->id);
        $lastReadAt = ConversationRead::query()
            ->select('last_read_at')
            ->whereColumn("{$readTable}.conversation_id", "{$conversationTable}.id")
            ->where('edition_participant_id', $participant->id)
            ->limit(1);
        $unreadCount = Message::query()
            ->selectRaw('count(*)')
            ->whereColumn("{$messageTable}.conversation_id", "{$conversationTable}.id")
            ->where('sender_edition_participant_id', '!=', $participant->id)
            ->where(function (Builder $messages) use ($readExists, $lastReadAt): void {
                $messages->whereNotExists($readExists)
                    ->orWhere('created_at', '>', $lastReadAt);
            });
        $baseQuery = Conversation::query()
            ->whereBelongsTo($edition)
            ->where(fn (Builder $conversations) => $conversations
                ->where('type', ConversationType::Edition)
                ->orWhereHas('assignment', fn (Builder $assignments) => $assignments
                    ->where('giver_edition_participant_id', $participant->id)
                    ->orWhere('receiver_edition_participant_id', $participant->id)))
            ->with(['assignment.giver.groupMember.user', 'assignment.receiver.groupMember.user'])
            ->withMax('messages', 'created_at')
            ->addSelect(['unread_count' => $unreadCount]);
        $conversations = QueryBuilder::for($baseQuery)
            ->allowedFilters()
            ->allowedIncludes()
            ->allowedSorts()
            ->allowedFields()
            ->orderBy('type', 'desc')
            ->orderBy('id')
            ->get();
        $items = $conversations->map(fn (Conversation $conversation): ConversationData => $this->conversationData(
            $conversation,
            $edition,
            $participant,
            $conversation->unread_count ?? 0,
        ));
        /** @var DataCollection<int, ConversationData> $data */
        $data = ConversationData::collect($items, DataCollection::class);

        return new ConversationCollectionData($data);
    }

    #[Authorize('view', 'conversation')]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/conversations/{conversation}/messages', operationId: 'getConversationMessages', tags: ['Conversations'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'conversation', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The latest messages in a visible conversation.', content: new OA\JsonContent(ref: '#/components/schemas/ConversationThread')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only conversation participants may read messages.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or conversation is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function messages(Group $group, Edition $edition, Conversation $conversation, Request $request): ConversationThreadData
    {
        $participant = $this->participantFor($edition, $request);
        $conversation->load(['assignment.giver.groupMember.user', 'assignment.receiver.groupMember.user']);
        $messages = QueryBuilder::for($conversation->messages()->getQuery())
            ->allowedFilters()
            ->allowedIncludes()
            ->allowedSorts()
            ->allowedFields('messages.id', 'messages.conversation_id', 'messages.sender_edition_participant_id', 'messages.body', 'messages.created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->with('sender.groupMember.user')
            ->get()
            ->reverse()
            ->values();
        $items = $messages->map(fn (Message $message): MessageData => MessageData::fromMessage($message, $conversation, $edition, $participant));
        /** @var DataCollection<int, MessageData> $data */
        $data = MessageData::collect($items, DataCollection::class);
        $lastMessageAt = $messages->last()?->created_at?->toIso8601String();

        return new ConversationThreadData(
            $this->conversationData($conversation, $edition, $participant, $this->unreadCount($conversation, $participant), $lastMessageAt),
            $data,
        );
    }

    #[Authorize('send', 'conversation')]
    #[OA\Post(
        path: '/api/v1/groups/{group}/editions/{edition}/conversations/{conversation}/messages', operationId: 'createMessage', tags: ['Conversations'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'conversation', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateMessageRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Message sent.', content: new OA\JsonContent(ref: '#/components/schemas/Message')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only conversation participants may send messages.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or conversation is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The conversation is not writable in the edition state.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 429, description: 'Too many messages were sent.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(CreateMessageData $data, Group $group, Edition $edition, Conversation $conversation, Request $request, SendMessage $sendMessage): MessageData
    {
        $participant = $this->participantFor($edition, $request);
        $conversation->load('assignment');
        $message = $sendMessage->handle($conversation, $participant, $data->body);
        $message->load('sender.groupMember.user');

        return MessageData::fromMessage($message, $conversation, $edition, $participant);
    }

    #[Authorize('view', 'conversation')]
    #[OA\Put(
        path: '/api/v1/groups/{group}/editions/{edition}/conversations/{conversation}/read', operationId: 'markConversationRead', tags: ['Conversations'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'conversation', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MarkConversationReadRequest')),
        responses: [
            new OA\Response(response: 204, description: 'Conversation marked as read.'),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only conversation participants may update read state.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or conversation is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function read(MarkConversationReadData $data, Group $group, Edition $edition, Conversation $conversation, Request $request, MarkConversationRead $markRead): Response
    {
        $markRead->handle($conversation, $this->participantFor($edition, $request), $data->messageId);

        return response()->noContent();
    }

    private function participantFor(Edition $edition, Request $request): EditionParticipant
    {
        /** @var User $user */
        $user = $request->user();

        return $edition->participants()
            ->whereHas('groupMember', fn ($members) => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->firstOrFail();
    }

    private function conversationData(
        Conversation $conversation,
        Edition $edition,
        EditionParticipant $participant,
        int $unreadCount,
        ?string $lastMessageAt = null,
    ): ConversationData {
        $lastMessageAt ??= $conversation->messages_max_created_at?->toIso8601String();

        return ConversationData::fromConversation($conversation, $edition, $participant, $unreadCount, $lastMessageAt);
    }

    private function unreadCount(Conversation $conversation, EditionParticipant $participant): int
    {
        $lastReadAt = $conversation->reads()
            ->whereBelongsTo($participant, 'editionParticipant')
            ->value('last_read_at');

        return $conversation->messages()
            ->where('sender_edition_participant_id', '!=', $participant->id)
            ->when($lastReadAt, fn (Builder $messages, mixed $readAt) => $messages->where('created_at', '>', $readAt))
            ->count();
    }
}
