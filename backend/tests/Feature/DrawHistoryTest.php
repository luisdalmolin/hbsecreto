<?php

use App\Actions\Draws\BuildDrawInput;
use App\Actions\Draws\PerformDraw;
use App\Models\Assignment;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('history uses member identity, recency weights, repetition, and lookback depth', function (): void {
    $creator = User::factory()->create();
    $group = Group::factory()->create(['created_by' => $creator->id]);
    $members = GroupMember::factory()->count(3)->for($group)->active()->create();
    $pastEditions = collect([
        ['drawn_at' => '2026-01-03 12:00:00', 'pair' => [0, 1]],
        ['drawn_at' => '2026-01-02 12:00:00', 'pair' => [0, 1]],
        ['drawn_at' => '2026-01-01 12:00:00', 'pair' => [1, 2]],
    ])->map(function (array $past) use ($creator, $group, $members): Edition {
        $edition = Edition::factory()->for($group)->drawn()->create([
            'created_by' => $creator->id,
            'drawn_at' => $past['drawn_at'],
        ]);
        $participants = $members->map(fn (GroupMember $member): EditionParticipant => EditionParticipant::factory()->for($edition)->create([
            'group_id' => $group->id,
            'group_member_id' => $member->id,
        ]));
        Assignment::query()->create([
            'edition_id' => $edition->id,
            'giver_edition_participant_id' => $participants[$past['pair'][0]]->id,
            'receiver_edition_participant_id' => $participants[$past['pair'][1]]->id,
        ]);

        return $edition;
    });

    $current = Edition::factory()->for($group)->open()->create([
        'created_by' => $creator->id,
        'settings' => ['historyLookbackDepth' => 2],
    ]);
    $participants = $members->map(fn (GroupMember $member): EditionParticipant => EditionParticipant::factory()->for($current)->create([
        'group_id' => $group->id,
        'group_member_id' => $member->id,
    ]));
    $input = app(BuildDrawInput::class)->handle($current, $participants, $current->drawConstraints()->get(), seed: 1);

    expect($pastEditions)->toHaveCount(3)
        ->and($input->history)->toHaveCount(2)
        ->and(collect($input->history)->pluck('giverGroupMemberId')->unique()->all())->toBe([$members[0]->id])
        ->and(collect($input->history)->pluck('receiverGroupMemberId')->unique()->all())->toBe([$members[1]->id])
        ->and(collect($input->history)->pluck('penalty')->sortDesc()->values()->all())->toBe([2, 1]);
});

test('claiming a placeholder preserves historical identity', function (): void {
    $creator = User::factory()->create();
    $claimedUser = User::factory()->create();
    $group = Group::factory()->create(['created_by' => $creator->id]);
    $placeholder = GroupMember::factory()->for($group)->create(['display_name' => 'Convidado']);
    $otherMember = GroupMember::factory()->for($group)->active()->create();
    $past = Edition::factory()->for($group)->drawn()->create(['created_by' => $creator->id, 'drawn_at' => '2026-01-01']);
    $pastGiver = EditionParticipant::factory()->for($past)->create(['group_id' => $group->id, 'group_member_id' => $placeholder->id]);
    $pastReceiver = EditionParticipant::factory()->for($past)->create(['group_id' => $group->id, 'group_member_id' => $otherMember->id]);
    Assignment::query()->create([
        'edition_id' => $past->id,
        'giver_edition_participant_id' => $pastGiver->id,
        'receiver_edition_participant_id' => $pastReceiver->id,
    ]);

    $placeholder->update(['user_id' => $claimedUser->id, 'status' => 'active', 'joined_at' => now()]);
    $current = Edition::factory()->for($group)->open()->create(['created_by' => $creator->id]);
    $currentGiver = EditionParticipant::factory()->for($current)->create(['group_id' => $group->id, 'group_member_id' => $placeholder->id]);
    $currentReceiver = EditionParticipant::factory()->for($current)->create(['group_id' => $group->id, 'group_member_id' => $otherMember->id]);
    $input = app(BuildDrawInput::class)->handle($current, new EloquentCollection([$currentGiver, $currentReceiver]), $current->drawConstraints()->get(), seed: 1);

    expect($input->participants[0]->groupMemberId)->toBe($placeholder->id)
        ->and($input->history[0]->giverGroupMemberId)->toBe($placeholder->id)
        ->and($input->history[0]->receiverGroupMemberId)->toBe($otherMember->id);
});

test('performed draw avoids historical pairs when a zero cost alternative exists', function (): void {
    $creator = User::factory()->create();
    $group = Group::factory()->create(['created_by' => $creator->id]);
    $members = GroupMember::factory()->count(4)->for($group)->active()->create();
    $baseline = Edition::factory()->for($group)->open()->create([
        'created_by' => $creator->id,
        'settings' => ['historyLookbackDepth' => 0],
    ]);
    $baselineParticipants = $members->map(fn (GroupMember $member): EditionParticipant => EditionParticipant::factory()->for($baseline)->create([
        'group_id' => $group->id,
        'group_member_id' => $member->id,
    ]));
    app(PerformDraw::class)->handle($baseline, seed: 17);
    $baselinePairs = Assignment::query()
        ->whereBelongsTo($baseline)
        ->with(['giver:id,group_member_id', 'receiver:id,group_member_id'])
        ->get()
        ->mapWithKeys(function (Assignment $assignment): array {
            $giver = $assignment->giver;
            $receiver = $assignment->receiver;

            if ($giver === null || $receiver === null) {
                throw new LogicException('A complete assignment must have both participants.');
            }

            return [$giver->group_member_id => $receiver->group_member_id];
        });

    $nextEdition = Edition::factory()->for($group)->open()->create([
        'created_by' => $creator->id,
        'settings' => ['historyLookbackDepth' => 5],
    ]);
    $members->each(fn (GroupMember $member): EditionParticipant => EditionParticipant::factory()->for($nextEdition)->create([
        'group_id' => $group->id,
        'group_member_id' => $member->id,
    ]));
    app(PerformDraw::class)->handle($nextEdition, seed: 17);
    $nextPairs = Assignment::query()
        ->whereBelongsTo($nextEdition)
        ->with(['giver:id,group_member_id', 'receiver:id,group_member_id'])
        ->get()
        ->mapWithKeys(function (Assignment $assignment): array {
            $giver = $assignment->giver;
            $receiver = $assignment->receiver;

            if ($giver === null || $receiver === null) {
                throw new LogicException('A complete assignment must have both participants.');
            }

            return [$giver->group_member_id => $receiver->group_member_id];
        });

    expect($baselineParticipants)->toHaveCount(4)
        ->and($baselinePairs)->toHaveCount(4)
        ->and($nextPairs)->toHaveCount(4)
        ->and($nextPairs->all())->not->toBe($baselinePairs->all());

    foreach ($baselinePairs as $giverMemberId => $receiverMemberId) {
        expect($nextPairs[$giverMemberId])->not->toBe($receiverMemberId);
    }
});
