<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $provider
 * @property string $external_id
 * @property string $title
 * @property string $url
 * @property string|null $affiliate_url
 * @property int|null $price_cents
 * @property string $currency
 * @property string|null $image_url
 * @property array<string, mixed> $raw
 * @property Carbon $fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Wish> $wishes
 */
#[Fillable(['provider', 'external_id', 'title', 'url', 'affiliate_url', 'price_cents', 'currency', 'image_url', 'raw', 'fetched_at'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @return HasMany<Wish, $this> */
    public function wishes(): HasMany
    {
        return $this->hasMany(Wish::class);
    }

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'raw' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
