<?php

namespace App\Models;

use App\Enums\EditionStatus;
use App\Policies\EditionPolicy;
use Database\Factories\EditionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property string $name
 * @property string $type
 * @property EditionStatus $status
 * @property int|null $budget_cents
 * @property string $currency
 * @property Carbon|null $event_date
 * @property array<string, mixed> $settings
 * @property Carbon|null $drawn_at
 * @property Carbon|null $revealed_at
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['group_id', 'name', 'type', 'status', 'budget_cents', 'currency', 'event_date', 'settings', 'drawn_at', 'revealed_at', 'created_by'])]
#[UsePolicy(EditionPolicy::class)]
class Edition extends Model
{
    /** @use HasFactory<EditionFactory> */
    use HasFactory;

    protected $attributes = [
        'type' => 'classic',
        'status' => EditionStatus::Draft->value,
        'currency' => 'BRL',
        'settings' => '{}',
    ];

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<EditionParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(EditionParticipant::class);
    }

    /** @return HasMany<DrawConstraint, $this> */
    public function drawConstraints(): HasMany
    {
        return $this->hasMany(DrawConstraint::class);
    }

    /** @return HasMany<Assignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    protected function casts(): array
    {
        return [
            'status' => EditionStatus::class,
            'budget_cents' => 'integer',
            'event_date' => 'date',
            'settings' => 'array',
            'drawn_at' => 'datetime',
            'revealed_at' => 'datetime',
        ];
    }
}
