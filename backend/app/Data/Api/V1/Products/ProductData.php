<?php

namespace App\Data\Api\V1\Products;

use App\Models\Product;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'Product', required: ['id', 'provider', 'externalId', 'title', 'url', 'affiliateUrl', 'priceCents', 'currency', 'imageUrl', 'fetchedAt'])]
final class ProductData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(example: 'mercadolivre')] public string $provider,
        #[OA\Property(example: 'MLB1234567890')] public string $externalId,
        #[OA\Property(example: 'Livro de ficção científica')] public string $title,
        #[OA\Property(format: 'uri', example: 'https://produto.mercadolivre.com.br/MLB-123')] public string $url,
        #[OA\Property(type: 'string', format: 'uri', nullable: true)] public ?string $affiliateUrl,
        #[OA\Property(type: 'integer', nullable: true, minimum: 0, example: 7990)] public ?int $priceCents,
        #[OA\Property(example: 'BRL', minLength: 3, maxLength: 3)] public string $currency,
        #[OA\Property(type: 'string', format: 'uri', nullable: true)] public ?string $imageUrl,
        #[OA\Property(type: 'string', format: 'date-time')] public string $fetchedAt,
    ) {}

    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            provider: $product->provider,
            externalId: $product->external_id,
            title: $product->title,
            url: $product->url,
            affiliateUrl: $product->affiliate_url,
            priceCents: $product->price_cents,
            currency: $product->currency,
            imageUrl: $product->image_url,
            fetchedAt: $product->fetched_at->toAtomString(),
        );
    }
}
