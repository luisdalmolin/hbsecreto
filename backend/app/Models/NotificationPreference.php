<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use App\Policies\NotificationPreferencePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property NotificationCategory $category
 * @property bool $push_enabled
 */
#[Fillable(['user_id', 'category', 'push_enabled'])]
#[UsePolicy(NotificationPreferencePolicy::class)]
class NotificationPreference extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'push_enabled' => 'boolean',
        ];
    }
}
