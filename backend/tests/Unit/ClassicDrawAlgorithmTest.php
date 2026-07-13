<?php

use App\Draws\ClassicDrawAlgorithm;
use App\Draws\DrawConstraintInput;
use App\Draws\DrawFailure;
use App\Draws\DrawFailureCode;
use App\Draws\DrawInput;
use App\Draws\DrawParticipant;
use App\Draws\DrawResult;
use App\Draws\HistoricalPair;
use App\Enums\DrawConstraintType;

function participants(int $count): array
{
    if ($count === 0) {
        return [];
    }

    return array_map(fn (int $id): DrawParticipant => new DrawParticipant($id, $id + 100), range(1, $count));
}

function drawWith(int $count, array $constraints = [], array $history = [], int $seed = 1): DrawResult
{
    return (new ClassicDrawAlgorithm)->draw(new DrawInput(participants($count), $constraints, $history, $seed));
}

test('draw requires at least two participants', function (int $count): void {
    expect(fn () => drawWith($count))->toThrow(DrawFailure::class, DrawFailureCode::InvalidParticipantCount->value);
})->with([0, 1]);

test('two participants receive each other exactly once', function (): void {
    $result = drawWith(2);

    expect($result->pairs)->toHaveCount(2)
        ->and(array_map(fn ($pair): array => [$pair->giverParticipantId, $pair->receiverParticipantId], $result->pairs))
        ->toBe([[1, 2], [2, 1]]);
});

test('two participants with an exclusion have no valid draw', function (): void {
    $constraint = new DrawConstraintInput(DrawConstraintType::MustNotPair, 1, 2);

    expect(fn () => drawWith(2, [$constraint]))->toThrow(DrawFailure::class, DrawFailureCode::NoValidAssignment->value);
});

test('three participants form a deranged permutation', function (): void {
    $result = drawWith(3);
    $givers = array_map(fn ($pair): int => $pair->giverParticipantId, $result->pairs);
    $receivers = array_map(fn ($pair): int => $pair->receiverParticipantId, $result->pairs);

    expect($givers)->toBe([1, 2, 3])
        ->and($receivers)->each->not->toBeIn([])
        ->and(array_unique($receivers))->toHaveCount(3);

    foreach ($result->pairs as $pair) {
        expect($pair->giverParticipantId)->not->toBe($pair->receiverParticipantId);
    }
});

test('a symmetric exclusion can make a three participant draw impossible', function (): void {
    expect(fn () => drawWith(3, [new DrawConstraintInput(DrawConstraintType::MustNotPair, 1, 2)]))
        ->toThrow(DrawFailure::class, DrawFailureCode::NoValidAssignment->value);
});

test('must pair is directional while must not pair is symmetric', function (): void {
    $forced = drawWith(4, [new DrawConstraintInput(DrawConstraintType::MustPair, 1, 2)]);
    $forcedMap = collect($forced->pairs)->mapWithKeys(fn ($pair): array => [$pair->giverParticipantId => $pair->receiverParticipantId]);

    expect($forcedMap[1])->toBe(2);

    $excluded = drawWith(4, [new DrawConstraintInput(DrawConstraintType::MustNotPair, 1, 2)]);
    $excludedMap = collect($excluded->pairs)->mapWithKeys(fn ($pair): array => [$pair->giverParticipantId => $pair->receiverParticipantId]);

    expect($excludedMap[1])->not->toBe(2)
        ->and($excludedMap[2])->not->toBe(1);
});

test('invalid and conflicting fixed edges fail with stable codes', function (): void {
    expect(fn () => drawWith(3, [new DrawConstraintInput(DrawConstraintType::MustPair, 1, 1)]))
        ->toThrow(DrawFailure::class, DrawFailureCode::InvalidConstraint->value);

    expect(fn () => drawWith(4, [
        new DrawConstraintInput(DrawConstraintType::MustPair, 1, 2),
        new DrawConstraintInput(DrawConstraintType::MustPair, 1, 3),
    ]))->toThrow(DrawFailure::class, DrawFailureCode::ConflictingConstraints->value);

    expect(fn () => drawWith(4, [
        new DrawConstraintInput(DrawConstraintType::MustPair, 1, 3),
        new DrawConstraintInput(DrawConstraintType::MustPair, 2, 3),
    ]))->toThrow(DrawFailure::class, DrawFailureCode::ConflictingConstraints->value);
});

test('Hall deficient candidate graph is rejected', function (): void {
    $constraints = [
        new DrawConstraintInput(DrawConstraintType::MustNotPair, 1, 2),
        new DrawConstraintInput(DrawConstraintType::MustNotPair, 1, 3),
        new DrawConstraintInput(DrawConstraintType::MustNotPair, 2, 3),
    ];

    expect(fn () => drawWith(4, $constraints))->toThrow(DrawFailure::class, DrawFailureCode::NoValidAssignment->value);
});

test('fixed seeds are deterministic and different seeds vary equal-cost optima', function (): void {
    $first = drawWith(6, seed: 7);
    $repeat = drawWith(6, seed: 7);
    $alternatives = collect(range(8, 20))->map(fn (int $seed): array => drawWith(6, seed: $seed)->pairs);

    expect($repeat)->toEqual($first)
        ->and($alternatives->contains(fn (array $pairs): bool => $pairs != $first->pairs))->toBeTrue();
});

test('history penalties accumulate by member identity and fixed edges override them', function (): void {
    $history = [
        new HistoricalPair(101, 102, 5),
        new HistoricalPair(101, 102, 4),
        new HistoricalPair(102, 103, 5),
    ];
    $result = drawWith(4, history: $history, seed: 3);
    $map = collect($result->pairs)->mapWithKeys(fn ($pair): array => [$pair->giverParticipantId => $pair->receiverParticipantId]);

    expect($map[1])->not->toBe(2)
        ->and($map[2])->not->toBe(3)
        ->and($result->totalPenalty)->toBe(0);

    $forced = drawWith(4, [new DrawConstraintInput(DrawConstraintType::MustPair, 1, 2)], $history, 3);
    $forcedMap = collect($forced->pairs)->mapWithKeys(fn ($pair): array => [$pair->giverParticipantId => $pair->receiverParticipantId]);

    expect($forcedMap[1])->toBe(2)
        ->and($forced->totalPenalty)->toBeGreaterThanOrEqual(9);
});
