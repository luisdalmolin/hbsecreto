<?php

use App\Enums\EditionStatus;
use App\Models\Assignment;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    app()->setLocale('pt_BR');
    $this->admin = User::factory()->create();
    $this->owner = User::factory()->create();
    $this->giver = User::factory()->create();
    $this->group = Group::factory()->create(['created_by' => $this->admin->id]);
    $this->adminMember = GroupMember::factory()->for($this->group)->active($this->admin)->admin()->create();
    $this->ownerMember = GroupMember::factory()->for($this->group)->active($this->owner)->create();
    $this->giverMember = GroupMember::factory()->for($this->group)->active($this->giver)->create();
    $this->edition = Edition::factory()->for($this->group)->open()->create(['created_by' => $this->admin->id]);
    $this->ownerParticipant = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->ownerMember->id,
    ]);
    $this->giverParticipant = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->giverMember->id,
    ]);
    $this->url = "/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/my-wishes";
    Sanctum::actingAs($this->owner);
});

test('participants can create update reorder list and delete their own wishes', function (): void {
    $first = $this->postJson($this->url, ['description' => '  Primeiro desejo  '])
        ->assertCreated()
        ->assertJson([
            'description' => 'Primeiro desejo',
            'sortOrder' => 0,
        ]);
    $second = $this->postJson($this->url, ['description' => 'Segundo desejo'])
        ->assertCreated()
        ->assertJsonPath('sortOrder', 1);
    $third = $this->postJson($this->url, ['description' => 'Terceiro desejo'])
        ->assertCreated()
        ->assertJsonPath('sortOrder', 2);

    $firstId = $first->json('id');
    $secondId = $second->json('id');
    $thirdId = $third->json('id');

    $this->patchJson("{$this->url}/{$firstId}", ['description' => 'Primeiro desejo atualizado'])
        ->assertOk()
        ->assertJsonPath('description', 'Primeiro desejo atualizado');
    $this->putJson("{$this->url}/order", ['wishIds' => [$thirdId, $firstId, $secondId]])
        ->assertOk()
        ->assertJsonPath('data.0.id', $thirdId)
        ->assertJsonPath('data.0.sortOrder', 0)
        ->assertJsonPath('data.1.id', $firstId)
        ->assertJsonPath('data.1.sortOrder', 1)
        ->assertJsonPath('data.2.id', $secondId)
        ->assertJsonPath('data.2.sortOrder', 2);

    $this->deleteJson("{$this->url}/{$firstId}")->assertNoContent();
    $this->getJson($this->url)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $thirdId)
        ->assertJsonPath('data.0.sortOrder', 0)
        ->assertJsonPath('data.1.id', $secondId)
        ->assertJsonPath('data.1.sortOrder', 1);
    expect(Wish::query()->find($firstId))->toBeNull();
});

test('wish descriptions must contain non-whitespace text within the length limit', function (): void {
    // These requests intentionally cross the OpenAPI boundary to exercise server validation.
    $this->withoutRequestValidation()->postJson($this->url, ['description' => '   '])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['description']);
    $this->withoutRequestValidation()->postJson($this->url, ['description' => str_repeat('a', 501)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['description']);

    $wish = Wish::factory()->for($this->ownerParticipant, 'editionParticipant')->create(['sort_order' => 0]);
    $this->withoutRequestValidation()->patchJson("{$this->url}/{$wish->id}", ['description' => " \n\t "])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['description']);
});

test('reordering requires each owner wish exactly once', function (): void {
    $first = Wish::factory()->for($this->ownerParticipant, 'editionParticipant')->create(['sort_order' => 0]);
    $second = Wish::factory()->for($this->ownerParticipant, 'editionParticipant')->create(['sort_order' => 1]);

    // Duplicate identifiers are deliberately invalid against the request schema.
    $this->withoutRequestValidation()->putJson("{$this->url}/order", ['wishIds' => [$first->id, $first->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['wishIds.1']);
    $this->putJson("{$this->url}/order", ['wishIds' => [$first->id]])
        ->assertConflict()
        ->assertJsonPath('message', 'A nova ordem precisa incluir todos os seus desejos uma única vez.');
    $this->putJson("{$this->url}/order", ['wishIds' => [$first->id, $second->id, 999999]])
        ->assertConflict()
        ->assertJsonPath('message', 'A nova ordem precisa incluir todos os seus desejos uma única vez.');
});

test('wishlist writes enforce owner and edition scoped binding privacy', function (): void {
    $otherWish = Wish::factory()->for($this->giverParticipant, 'editionParticipant')->create(['sort_order' => 0]);

    $this->patchJson("{$this->url}/{$otherWish->id}", ['description' => 'Tentativa'])
        ->assertForbidden();
    $this->deleteJson("{$this->url}/{$otherWish->id}")
        ->assertForbidden();

    $otherEdition = Edition::factory()->for($this->group)->open()->create(['created_by' => $this->admin->id]);
    $otherParticipant = EditionParticipant::factory()->for($otherEdition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $this->ownerMember->id,
    ]);
    $crossEditionWish = Wish::factory()->for($otherParticipant, 'editionParticipant')->create(['sort_order' => 0]);

    $this->patchJson("{$this->url}/{$crossEditionWish->id}", ['description' => 'Tentativa'])
        ->assertNotFound();
    $this->deleteJson("{$this->url}/{$crossEditionWish->id}")
        ->assertNotFound();
});

test('an administrator outside the roster has no implicit wishlist access', function (): void {
    Sanctum::actingAs($this->admin);

    $this->getJson($this->url)->assertForbidden();
    $this->postJson($this->url, ['description' => 'Desejo administrativo'])->assertForbidden();
    $this->putJson("{$this->url}/order", ['wishIds' => []])->assertForbidden();
});

test('deactivated participants lose wishlist access', function (): void {
    $wish = Wish::factory()->for($this->ownerParticipant, 'editionParticipant')->create(['sort_order' => 0]);
    $this->ownerMember->update(['status' => 'inactive']);

    $this->getJson($this->url)->assertNotFound();
    $this->patchJson("{$this->url}/{$wish->id}", ['description' => 'Tentativa'])->assertNotFound();
});

test('archived wishes remain readable and reject every write', function (): void {
    $wish = Wish::factory()->for($this->ownerParticipant, 'editionParticipant')->create(['sort_order' => 0]);
    $this->edition->update(['status' => EditionStatus::Archived]);

    $this->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.0.id', $wish->id);
    $this->postJson($this->url, ['description' => 'Novo desejo'])
        ->assertConflict()
        ->assertJsonPath('message', 'A lista de desejos não pode mais ser alterada porque a edição foi arquivada.');
    $this->patchJson("{$this->url}/{$wish->id}", ['description' => 'Atualizado'])
        ->assertConflict()
        ->assertJsonPath('message', 'A lista de desejos não pode mais ser alterada porque a edição foi arquivada.');
    $this->putJson("{$this->url}/order", ['wishIds' => [$wish->id]])
        ->assertConflict()
        ->assertJsonPath('message', 'A lista de desejos não pode mais ser alterada porque a edição foi arquivada.');
    $this->deleteJson("{$this->url}/{$wish->id}")
        ->assertConflict()
        ->assertJsonPath('message', 'A lista de desejos não pode mais ser alterada porque a edição foi arquivada.');
    $this->assertModelExists($wish);
});

test('a giver receives only their assigned receiver ordered wishes', function (): void {
    $laterWish = Wish::factory()->for($this->ownerParticipant, 'editionParticipant')->create([
        'description' => 'Segundo na lista',
        'sort_order' => 1,
    ]);
    $firstWish = Wish::factory()->for($this->ownerParticipant, 'editionParticipant')->create([
        'description' => 'Primeiro na lista',
        'sort_order' => 0,
    ]);
    Wish::factory()->for($this->giverParticipant, 'editionParticipant')->create([
        'description' => 'Desejo privado do giver',
        'sort_order' => 0,
    ]);
    Assignment::factory()->create([
        'edition_id' => $this->edition->id,
        'giver_edition_participant_id' => $this->giverParticipant->id,
        'receiver_edition_participant_id' => $this->ownerParticipant->id,
    ]);
    Assignment::factory()->create([
        'edition_id' => $this->edition->id,
        'giver_edition_participant_id' => $this->ownerParticipant->id,
        'receiver_edition_participant_id' => $this->giverParticipant->id,
    ]);
    $this->edition->update(['status' => EditionStatus::Drawn, 'drawn_at' => now()]);
    Sanctum::actingAs($this->giver);

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/my-assignment")
        ->assertOk()
        ->assertJsonCount(2, 'wishes')
        ->assertJsonPath('wishes.0.id', $firstWish->id)
        ->assertJsonPath('wishes.0.sortOrder', 0)
        ->assertJsonPath('wishes.1.id', $laterWish->id)
        ->assertJsonMissing(['description' => 'Desejo privado do giver']);

    expect(Gate::forUser($this->giver)->allows('view', $firstWish))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('view', $firstWish))->toBeFalse();
});
