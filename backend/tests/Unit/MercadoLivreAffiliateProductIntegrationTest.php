<?php

use App\Exceptions\AffiliateProductCatalogUnavailable;
use App\Services\AffiliateProducts\MercadoLivreAffiliateProductIntegration;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('the Mercado Livre integration maps marketplace listings into typed products', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.mercadolibre.com/sites/MLB/search*' => Http::response([
            'results' => [
                [
                    'id' => 'MLB1234567890',
                    'title' => 'Livro especial',
                    'permalink' => 'https://produto.mercadolivre.com.br/MLB-1234567890',
                    'price' => 79.9,
                    'currency_id' => 'brl',
                    'secure_thumbnail' => 'https://http2.mlstatic.com/example.jpg',
                    'seller' => ['id' => 42],
                ],
                ['id' => null, 'title' => 'Resultado inválido'],
            ],
        ]),
    ]);
    $catalog = new MercadoLivreAffiliateProductIntegration(
        http: app(Factory::class),
        baseUrl: 'https://api.mercadolibre.com',
        siteId: 'MLB',
        accessToken: 'access-token',
        timeout: 10,
        connectTimeout: 3,
    );

    $products = $catalog->search('livro', 10);

    expect($products)->toHaveCount(1)
        ->and($products[0]->provider)->toBe('mercadolivre')
        ->and($products[0]->externalId)->toBe('MLB1234567890')
        ->and($products[0]->priceCents)->toBe(7990)
        ->and($products[0]->currency)->toBe('BRL')
        ->and($products[0]->raw['seller'])->toBe(['id' => 42]);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access-token')
        && $request->url() === 'https://api.mercadolibre.com/sites/MLB/search?q=livro&limit=10');
});

test('the Mercado Livre integration reports malformed catalog responses', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.mercadolibre.com/sites/MLB/search*' => Http::response(['unexpected' => []]),
    ]);
    $catalog = new MercadoLivreAffiliateProductIntegration(
        http: app(Factory::class),
        baseUrl: 'https://api.mercadolibre.com',
        siteId: 'MLB',
        accessToken: '',
        timeout: 10,
        connectTimeout: 3,
    );

    expect(fn () => $catalog->search('livro', 10))->toThrow(AffiliateProductCatalogUnavailable::class);
});
