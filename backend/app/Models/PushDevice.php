<?php

namespace App\Models;

use App\Enums\PushPlatform;
use App\Policies\PushDevicePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $personal_access_token_id
 * @property string $expo_push_token
 * @property PushPlatform $platform
 * @property string|null $device_name
 * @property Carbon $last_registered_at
 * @property Carbon|null $disabled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'personal_access_token_id', 'expo_push_token', 'platform', 'device_name', 'last_registered_at', 'disabled_at'])]
#[UsePolicy(PushDevicePolicy::class)]
class PushDevice extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<PersonalAccessToken, $this> */
    public function personalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class);
    }

    /** @return HasMany<PushDelivery, $this> */
    public function pushDeliveries(): HasMany
    {
        return $this->hasMany(PushDelivery::class);
    }

    /** @param Builder<PushDevice> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('disabled_at');
    }

    protected function casts(): array
    {
        return [
            'platform' => PushPlatform::class,
            'last_registered_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }
}
