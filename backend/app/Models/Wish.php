<?php

namespace App\Models;

use App\Policies\WishPolicy;
use Database\Factories\WishFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $edition_participant_id
 * @property string $description
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EditionParticipant|null $editionParticipant
 */
#[Fillable(['edition_participant_id', 'description', 'sort_order'])]
#[UsePolicy(WishPolicy::class)]
class Wish extends Model
{
    /** @use HasFactory<WishFactory> */
    use HasFactory;

    /** @return BelongsTo<EditionParticipant, $this> */
    public function editionParticipant(): BelongsTo
    {
        return $this->belongsTo(EditionParticipant::class);
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
