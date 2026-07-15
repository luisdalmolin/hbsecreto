<?php

namespace App\Models;

use App\Enums\ConversationType;
use App\Policies\ConversationPolicy;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $edition_id
 * @property ConversationType $type
 * @property int|null $assignment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $unread_count
 * @property Carbon|null $messages_max_created_at
 * @property-read Assignment|null $assignment
 * @property-read Collection<int, Message> $messages
 * @property-read Collection<int, ConversationRead> $reads
 */
#[Fillable(['edition_id', 'type', 'assignment_id'])]
#[UsePolicy(ConversationPolicy::class)]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Assignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** @return HasMany<ConversationRead, $this> */
    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class);
    }

    protected function casts(): array
    {
        return [
            'type' => ConversationType::class,
            'unread_count' => 'integer',
            'messages_max_created_at' => 'datetime',
        ];
    }
}
