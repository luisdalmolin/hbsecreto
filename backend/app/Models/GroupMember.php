<?php

namespace App\Models;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Policies\GroupMemberPolicy;
use Database\Factories\GroupMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int|null $user_id
 * @property string|null $display_name
 * @property string|null $email
 * @property GroupMemberRole $role
 * @property string|null $invite_token
 * @property Carbon|null $invite_expires_at
 * @property GroupMemberStatus $status
 * @property Carbon|null $joined_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['group_id', 'user_id', 'display_name', 'email', 'role', 'invite_token', 'invite_expires_at', 'status', 'joined_at'])]
#[Hidden(['invite_token'])]
#[UsePolicy(GroupMemberPolicy::class)]
class GroupMember extends Model
{
    /** @use HasFactory<GroupMemberFactory> */
    use HasFactory;

    protected $attributes = [
        'role' => GroupMemberRole::Member->value,
        'status' => GroupMemberStatus::Invited->value,
    ];

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EditionParticipant, $this> */
    public function editionParticipants(): HasMany
    {
        return $this->hasMany(EditionParticipant::class);
    }

    /** @param Builder<GroupMember> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', GroupMemberStatus::Active);
    }

    protected function casts(): array
    {
        return [
            'role' => GroupMemberRole::class,
            'status' => GroupMemberStatus::class,
            'invite_expires_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }
}
