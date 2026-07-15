<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\Edition;

final class CreateAssignmentConversations
{
    public function handle(Edition $edition): void
    {
        $timestamp = now();
        $rows = $edition->assignments()
            ->whereDoesntHave('conversation')
            ->orderBy('id')
            ->get(['id', 'edition_id'])
            ->map(fn ($assignment): array => [
                'edition_id' => $assignment->edition_id,
                'type' => ConversationType::Assignment->value,
                'assignment_id' => $assignment->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        if ($rows !== []) {
            Conversation::query()->insertOrIgnore($rows);
        }
    }
}
