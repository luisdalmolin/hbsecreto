<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Services\AffiliateProducts\AffiliateProductCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SearchAffiliateProducts
{
    public function __construct(private AffiliateProductCatalog $catalog) {}

    /** @return Collection<int, Product> */
    public function handle(string $query, int $limit): Collection
    {
        $results = $this->catalog->search(Str::of($query)->trim()->toString(), $limit);

        return DB::transaction(function () use ($results): Collection {
            $products = new Collection;

            foreach ($results as $result) {
                $product = Product::query()->updateOrCreate(
                    [
                        'provider' => $result->provider,
                        'external_id' => $result->externalId,
                    ],
                    [
                        'title' => $result->title,
                        'url' => $result->url,
                        'affiliate_url' => $result->affiliateUrl,
                        'price_cents' => $result->priceCents,
                        'currency' => $result->currency,
                        'image_url' => $result->imageUrl,
                        'raw' => $result->raw,
                        'fetched_at' => now(),
                    ],
                );
                $products->push($product);
            }

            return $products;
        }, attempts: 3);
    }
}
