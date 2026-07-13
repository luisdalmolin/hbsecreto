<?php

namespace App\Models;

use App\Enums\GroupMemberStatus;
use App\Policies\GroupPolicy;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'description', 'created_by'])]
#[UsePolicy(GroupPolicy::class)]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory, SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<GroupMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /** @return HasMany<Edition, $this> */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class);
    }

    /** @param Builder<Group> $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->whereHas('members', fn (Builder $members): Builder => $members
            ->whereBelongsTo($user)
            ->where('status', GroupMemberStatus::Active));
    }

    /**
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        return static::query()
            ->visibleTo($user)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
