<?php

namespace App\Models;

use App\Policies\EditionParticipantPolicy;
use Database\Factories\EditionParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $edition_id
 * @property int $group_id
 * @property int $group_member_id
 * @property-read Collection<int, Wish> $wishes
 */
#[Fillable(['edition_id', 'group_id', 'group_member_id'])]
#[UsePolicy(EditionParticipantPolicy::class)]
class EditionParticipant extends Model
{
    /** @use HasFactory<EditionParticipantFactory> */
    use HasFactory;

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<GroupMember, $this> */
    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class);
    }

    /** @return HasMany<Assignment, $this> */
    public function givenAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'giver_edition_participant_id');
    }

    /** @return HasMany<Assignment, $this> */
    public function receivedAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'receiver_edition_participant_id');
    }

    /** @return HasMany<Wish, $this> */
    public function wishes(): HasMany
    {
        return $this->hasMany(Wish::class)->orderBy('sort_order')->orderBy('id');
    }
}
