<?php

use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('group member and edition states are protected by database checks', function (): void {
    $group = Group::factory()->create();

    expect(fn () => DB::table((new GroupMember)->getTable())->insert([
        'group_id' => $group->id,
        'role' => 'member',
        'status' => 'unknown',
        'created_at' => now(),
        'updated_at' => now(),
    ]))
        ->toThrow(QueryException::class)
        ->and(fn () => DB::table((new Edition)->getTable())->insert([
            'group_id' => $group->id,
            'name' => 'Inválida',
            'type' => 'classic',
            'status' => 'unknown',
            'currency' => 'BRL',
            'settings' => '{}',
            'created_by' => $group->created_by,
            'created_at' => now(),
            'updated_at' => now(),
        ]))
        ->toThrow(QueryException::class);
});

test('claimed memberships and edition roster identities are unique', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $member = GroupMember::factory()->for($group)->active($user)->create();
    $edition = Edition::factory()->for($group)->create();
    EditionParticipant::factory()->for($edition)->create([
        'group_id' => $group->id,
        'group_member_id' => $member->id,
    ]);

    expect(fn () => GroupMember::factory()->for($group)->active($user)->create())
        ->toThrow(QueryException::class)
        ->and(fn () => EditionParticipant::factory()->for($edition)->create([
            'group_id' => $group->id,
            'group_member_id' => $member->id,
        ]))->toThrow(QueryException::class);
});

test('the database rejects cross group edition participants', function (): void {
    $editionGroup = Group::factory()->create();
    $memberGroup = Group::factory()->create();
    $edition = Edition::factory()->for($editionGroup)->create();
    $member = GroupMember::factory()->for($memberGroup)->create();

    expect(fn () => EditionParticipant::query()->create([
        'edition_id' => $edition->id,
        'group_id' => $editionGroup->id,
        'group_member_id' => $member->id,
    ]))->toThrow(QueryException::class);
});
