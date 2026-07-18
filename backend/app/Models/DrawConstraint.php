<?php

namespace App\Models;

use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Enums\OrderStatus;
use Database\Factories\DrawConstraintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $edition_id
 * @property DrawConstraintType $type
 * @property int $giver_edition_participant_id
 * @property int $receiver_edition_participant_id
 * @property DrawConstraintSource $source
 * @property int|null $order_id
 * @property int|null $created_by
 */
#[Fillable(['edition_id', 'type', 'giver_edition_participant_id', 'receiver_edition_participant_id', 'source', 'order_id', 'created_by'])]
class DrawConstraint extends Model
{
    /** @use HasFactory<DrawConstraintFactory> */
    use HasFactory;

    protected $attributes = ['source' => DrawConstraintSource::Admin->value];

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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @param Builder<DrawConstraint> $query
     * @return Builder<DrawConstraint>
     */
    public function scopeActiveForDraw(Builder $query): Builder
    {
        return $query->where(function (Builder $constraints): void {
            $constraints->where('source', DrawConstraintSource::Admin)
                ->orWhere(function (Builder $purchases): void {
                    $purchases->where('source', DrawConstraintSource::Purchase)
                        ->whereHas('order', fn (Builder $orders): Builder => $orders->where('status', OrderStatus::Paid));
                });
        });
    }

    protected function casts(): array
    {
        return ['type' => DrawConstraintType::class, 'source' => DrawConstraintSource::class];
    }
}
