<?php

use App\Models\Assignment;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('wishes can link cached products and preserve or clear that link on update', function (): void {
    $owner = User::factory()->create();
    $group = Group::factory()->create();
    $member = GroupMember::factory()->for($group)->active($owner)->create();
    $edition = Edition::factory()->for($group)->open()->create();
    EditionParticipant::factory()->for($edition)->create([
        'group_id' => $group->id,
        'group_member_id' => $member->id,
    ]);
    $product = Product::factory()->create([
        'title' => 'Livro especial',
        'price_cents' => 7990,
    ]);
    $url = "/api/v1/groups/{$group->id}/editions/{$edition->id}/my-wishes";
    Sanctum::actingAs($owner);

    $wishId = $this->postJson($url, [
        'description' => 'Quero este livro',
        'productId' => $product->id,
    ])->assertCreated()
        ->assertJsonPath('product.id', $product->id)
        ->assertJsonPath('product.title', 'Livro especial')
        ->assertJsonPath('product.priceCents', 7990)
        ->json('id');

    $this->patchJson("{$url}/{$wishId}", ['description' => 'Quero muito este livro'])
        ->assertOk()
        ->assertJsonPath('product.id', $product->id);
    $this->patchJson("{$url}/{$wishId}", [
        'description' => 'Aceito outra edição',
        'productId' => null,
    ])->assertOk()->assertJsonPath('product', null);

    // The identifier is valid OpenAPI input but does not reference a cached product.
    $this->patchJson("{$url}/{$wishId}", [
        'description' => 'Produto inexistente',
        'productId' => 999999,
    ])->assertUnprocessable()->assertJsonValidationErrors(['productId']);
});

test('an assignment serializes the receivers product-backed wishes', function (): void {
    $owner = User::factory()->create();
    $giver = User::factory()->create();
    $group = Group::factory()->create();
    $ownerMember = GroupMember::factory()->for($group)->active($owner)->create();
    $giverMember = GroupMember::factory()->for($group)->active($giver)->create();
    $edition = Edition::factory()->for($group)->open()->create();
    $ownerParticipant = EditionParticipant::factory()->for($edition)->create([
        'group_id' => $group->id,
        'group_member_id' => $ownerMember->id,
    ]);
    $giverParticipant = EditionParticipant::factory()->for($edition)->create([
        'group_id' => $group->id,
        'group_member_id' => $giverMember->id,
    ]);
    $product = Product::factory()->create(['title' => 'Presente escolhido']);
    $ownerParticipant->wishes()->create([
        'description' => 'Este aqui',
        'product_id' => $product->id,
        'sort_order' => 0,
    ]);
    Assignment::factory()->create([
        'edition_id' => $edition->id,
        'giver_edition_participant_id' => $giverParticipant->id,
        'receiver_edition_participant_id' => $ownerParticipant->id,
    ]);
    $edition->update(['status' => 'drawn', 'drawn_at' => now()]);
    Sanctum::actingAs($giver);

    $this->getJson("/api/v1/groups/{$group->id}/editions/{$edition->id}/my-assignment")
        ->assertOk()
        ->assertJsonPath('wishes.0.product.id', $product->id)
        ->assertJsonPath('wishes.0.product.title', 'Presente escolhido');
});
