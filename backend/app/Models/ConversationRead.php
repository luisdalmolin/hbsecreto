<?php

namespace App\Models;

use Database\Factories\ConversationReadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $edition_id
 * @property int $conversation_id
 * @property int $edition_participant_id
 * @property Carbon $last_read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['edition_id', 'conversation_id', 'edition_participant_id', 'last_read_at'])]
class ConversationRead extends Model
{
    /** @use HasFactory<ConversationReadFactory> */
    use HasFactory;

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<EditionParticipant, $this> */
    public function editionParticipant(): BelongsTo
    {
        return $this->belongsTo(EditionParticipant::class);
    }

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
        ];
    }
}
