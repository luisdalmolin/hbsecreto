<?php

use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Product;
use App\Models\User;
use App\Services\AffiliateProducts\AffiliateProductCatalog;
use App\Services\AffiliateProducts\AffiliateProductData;
use App\Services\AffiliateProducts\FakeAffiliateProductIntegration;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->member = GroupMember::factory()->for($this->group)->active($this->user)->create();
    $this->edition = Edition::factory()->for($this->group)->open()->create();
    $this->participant = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->member->id,
    ]);
    $this->url = "/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/products/search";
    Sanctum::actingAs($this->user);
});

test('active participants can search and cache affiliate products', function (): void {
    $catalog = app(AffiliateProductCatalog::class);

    if (! $catalog instanceof FakeAffiliateProductIntegration) {
        throw new LogicException('Tests must use the fake affiliate product integration.');
    }

    $catalog->replaceResults(new AffiliateProductData(
        provider: 'mercadolivre',
        externalId: 'MLB123',
        title: 'Livro original',
        url: 'https://produto.mercadolivre.com.br/MLB-123',
        affiliateUrl: null,
        priceCents: 7990,
        currency: 'BRL',
        imageUrl: 'https://http2.mlstatic.com/book.jpg',
        raw: ['id' => 'MLB123', 'version' => 1],
    ));

    $response = $this->getJson("{$this->url}?q=livro&limit=5")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.provider', 'mercadolivre')
        ->assertJsonPath('data.0.externalId', 'MLB123')
        ->assertJsonPath('data.0.priceCents', 7990)
        ->assertJsonPath('data.0.currency', 'BRL');

    $productId = $response->json('data.0.id');
    expect(Product::query()->findOrFail($productId)->raw)->toBe(['id' => 'MLB123', 'version' => 1])
        ->and($catalog->searches())->toBe([['query' => 'livro', 'limit' => 5]]);

    $catalog->replaceResults(new AffiliateProductData(
        provider: 'mercadolivre',
        externalId: 'MLB123',
        title: 'Livro atualizado',
        url: 'https://produto.mercadolivre.com.br/MLB-123',
        affiliateUrl: 'https://mercadolivre.com/sec/affiliate',
        priceCents: 6990,
        currency: 'BRL',
        imageUrl: null,
        raw: ['id' => 'MLB123', 'version' => 2],
    ));

    $this->getJson("{$this->url}?q=livro")
        ->assertOk()
        ->assertJsonPath('data.0.id', $productId)
        ->assertJsonPath('data.0.title', 'Livro atualizado')
        ->assertJsonPath('data.0.affiliateUrl', 'https://mercadolivre.com/sec/affiliate');
    expect(Product::query()->count())->toBe(1);
});

test('catalog search requires an active edition participant', function (): void {
    $administrator = User::factory()->create();
    GroupMember::factory()->for($this->group)->active($administrator)->admin()->create();
    Sanctum::actingAs($administrator);

    $this->getJson("{$this->url}?q=livro")->assertForbidden();

    $this->member->update(['status' => 'inactive']);
    Sanctum::actingAs($this->user);
    $this->getJson("{$this->url}?q=livro")->assertNotFound();
});

test('catalog search validates its query without calling the provider', function (): void {
    $catalog = app(AffiliateProductCatalog::class);

    if (! $catalog instanceof FakeAffiliateProductIntegration) {
        throw new LogicException('Tests must use the fake affiliate product integration.');
    }

    // These requests intentionally cross the OpenAPI boundary to exercise server validation.
    $this->withoutRequestValidation()->getJson("{$this->url}?q=x&limit=21")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q', 'limit']);
    expect($catalog->searches())->toBeEmpty();
});

test('catalog outages return a localized gateway error', function (): void {
    $catalog = app(AffiliateProductCatalog::class);

    if (! $catalog instanceof FakeAffiliateProductIntegration) {
        throw new LogicException('Tests must use the fake affiliate product integration.');
    }

    $catalog->simulateUnavailable();

    $this->getJson("{$this->url}?q=livro")
        ->assertStatus(502)
        ->assertJsonPath('message', 'Não foi possível consultar o catálogo de produtos agora. Tente novamente em instantes.');
});
