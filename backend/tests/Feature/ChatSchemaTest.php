<?php

use App\Models\Assignment;
use App\Models\Conversation;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('conversation types and assignment edition identity are protected by database checks', function (): void {
    $group = Group::factory()->create();
    $edition = Edition::factory()->for($group)->drawn()->create();
    $giver = EditionParticipant::factory()->for($edition)->create(['group_id' => $group->id]);
    $receiver = EditionParticipant::factory()->for($edition)->create(['group_id' => $group->id]);
    $assignment = Assignment::factory()->create([
        'edition_id' => $edition->id,
        'giver_edition_participant_id' => $giver->id,
        'receiver_edition_participant_id' => $receiver->id,
    ]);

    expect(fn () => DB::table((new Conversation)->getTable())->insert([
        'edition_id' => $edition->id,
        'type' => 'unknown',
        'assignment_id' => $assignment->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table((new Conversation)->getTable())->insert([
            'edition_id' => $edition->id,
            'type' => 'assignment',
            'assignment_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]))->toThrow(QueryException::class);

    $otherEdition = Edition::factory()->for($group)->drawn()->create();
    expect(fn () => DB::table((new Conversation)->getTable())->insert([
        'edition_id' => $otherEdition->id,
        'type' => 'assignment',
        'assignment_id' => $assignment->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
