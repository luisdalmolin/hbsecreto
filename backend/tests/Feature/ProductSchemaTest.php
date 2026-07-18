<?php

use App\Models\EditionParticipant;
use App\Models\Product;
use App\Models\Wish;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('affiliate products are unique within their provider and may be shared by wishes', function (): void {
    $product = Product::factory()->create([
        'provider' => 'mercadolivre',
        'external_id' => 'MLB123',
    ]);
    Product::factory()->create([
        'provider' => 'amazon',
        'external_id' => 'MLB123',
    ]);
    $firstWish = Wish::factory()->for(EditionParticipant::factory(), 'editionParticipant')->create([
        'product_id' => $product->id,
    ]);
    $secondWish = Wish::factory()->for(EditionParticipant::factory(), 'editionParticipant')->create([
        'product_id' => $product->id,
    ]);

    expect(fn () => Product::factory()->create([
        'provider' => 'mercadolivre',
        'external_id' => 'MLB123',
    ]))->toThrow(QueryException::class)
        ->and($product->wishes()->pluck('id')->all())->toContain($firstWish->id, $secondWish->id);
});

test('deleting a cached product preserves free text wishes', function (): void {
    $product = Product::factory()->create();
    $wish = Wish::factory()->create([
        'description' => 'Ainda quero este presente',
        'product_id' => $product->id,
    ]);

    $product->delete();

    expect($wish->refresh()->product_id)->toBeNull()
        ->and($wish->description)->toBe('Ainda quero este presente');
});
