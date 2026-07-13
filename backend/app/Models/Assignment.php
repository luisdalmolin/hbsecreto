<?php

namespace App\Models;

use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $edition_id
 * @property int $giver_edition_participant_id
 * @property int $receiver_edition_participant_id
 * @property-read EditionParticipant|null $giver
 * @property-read EditionParticipant|null $receiver
 */
#[Fillable(['edition_id', 'giver_edition_participant_id', 'receiver_edition_participant_id'])]
class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<EditionParticipant, $this> */
    public function giver(): BelongsTo
    {
        return $this->belongsTo(EditionParticipant::class, 'giver_edition_participant_id');
    }

    /** @return BelongsTo<EditionParticipant, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(EditionParticipant::class, 'receiver_edition_participant_id');
    }
}
