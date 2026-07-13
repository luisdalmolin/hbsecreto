<?php

use App\Models\Assignment;
use App\Models\DrawConstraint;
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

function drawSchemaFixture(): array
{
    $user = User::factory()->create();
    $group = Group::factory()->create(['created_by' => $user->id]);
    $edition = Edition::factory()->for($group)->create(['created_by' => $user->id]);
    $members = GroupMember::factory()->count(3)->for($group)->active()->create();
    $participants = $members->map(fn (GroupMember $member): EditionParticipant => EditionParticipant::factory()->for($edition)->create([
        'group_id' => $group->id,
        'group_member_id' => $member->id,
    ]));

    return [$user, $group, $edition, $members, $participants];
}

test('draw constraint values and normalized pairs are protected', function (): void {
    [$user, , $edition, , $participants] = drawSchemaFixture();
    $values = [
        'edition_id' => $edition->id,
        'type' => 'unknown',
        'giver_edition_participant_id' => $participants[0]->id,
        'receiver_edition_participant_id' => $participants[1]->id,
        'source' => 'admin',
        'created_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    expect(fn () => DB::table((new DrawConstraint)->getTable())->insert($values))->toThrow(QueryException::class);

    $values['type'] = 'must_not_pair';
    DB::table((new DrawConstraint)->getTable())->insert($values);

    expect(fn () => DB::table((new DrawConstraint)->getTable())->insert($values))->toThrow(QueryException::class);

    $reverse = $values;
    $reverse['giver_edition_participant_id'] = $participants[1]->id;
    $reverse['receiver_edition_participant_id'] = $participants[0]->id;
    expect(fn () => DB::table((new DrawConstraint)->getTable())->insert($reverse))->toThrow(QueryException::class);

    $self = $values;
    $self['receiver_edition_participant_id'] = $participants[0]->id;
    expect(fn () => DB::table((new DrawConstraint)->getTable())->insert($self))->toThrow(QueryException::class);
});

test('assignments reject self edges and duplicate giver or receiver', function (): void {
    [, , $edition, , $participants] = drawSchemaFixture();
    $assignment = [
        'edition_id' => $edition->id,
        'giver_edition_participant_id' => $participants[0]->id,
        'receiver_edition_participant_id' => $participants[0]->id,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    expect(fn () => DB::table((new Assignment)->getTable())->insert($assignment))->toThrow(QueryException::class);

    $assignment['receiver_edition_participant_id'] = $participants[1]->id;
    DB::table((new Assignment)->getTable())->insert($assignment);

    $duplicateGiver = $assignment;
    $duplicateGiver['receiver_edition_participant_id'] = $participants[2]->id;
    expect(fn () => DB::table((new Assignment)->getTable())->insert($duplicateGiver))->toThrow(QueryException::class);

    $duplicateReceiver = $assignment;
    $duplicateReceiver['giver_edition_participant_id'] = $participants[2]->id;
    expect(fn () => DB::table((new Assignment)->getTable())->insert($duplicateReceiver))->toThrow(QueryException::class);
});

test('composite keys reject cross-edition participants and assignments restrict participant deletion', function (): void {
    [$user, $group, $edition, $members, $participants] = drawSchemaFixture();
    $otherEdition = Edition::factory()->for($group)->create(['created_by' => $user->id]);
    $otherParticipant = EditionParticipant::factory()->for($otherEdition)->create([
        'group_id' => $group->id,
        'group_member_id' => $members[0]->id,
    ]);
    $values = [
        'edition_id' => $edition->id,
        'giver_edition_participant_id' => $participants[0]->id,
        'receiver_edition_participant_id' => $otherParticipant->id,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    expect(fn () => DB::table((new Assignment)->getTable())->insert($values))->toThrow(QueryException::class);

    $values['receiver_edition_participant_id'] = $participants[1]->id;
    DB::table((new Assignment)->getTable())->insert($values);

    expect(fn () => $participants[0]->delete())->toThrow(QueryException::class);
});
