<?php

use App\Draws\ClassicDrawAlgorithm;
use App\Draws\DrawFailure;
use App\Draws\DrawFailureCode;
use App\Draws\EditionTypeRegistry;

test('registry resolves classic and validates history lookback settings', function (): void {
    $classic = new ClassicDrawAlgorithm;
    $registry = new EditionTypeRegistry($classic);

    expect($registry->resolve('classic'))->toBe($classic)
        ->and($registry->historyLookbackDepth([]))->toBe(5)
        ->and($registry->historyLookbackDepth(['historyLookbackDepth' => 0]))->toBe(0)
        ->and($registry->historyLookbackDepth(['historyLookbackDepth' => 20]))->toBe(20);
});

test('registry rejects unknown types and out of range settings', function (): void {
    $registry = new EditionTypeRegistry(new ClassicDrawAlgorithm);

    expect(fn () => $registry->resolve('unknown'))->toThrow(DrawFailure::class, DrawFailureCode::InvalidEditionState->value)
        ->and(fn () => $registry->historyLookbackDepth(['historyLookbackDepth' => 21]))->toThrow(DrawFailure::class)
        ->and(fn () => $registry->historyLookbackDepth(['historyLookbackDepth' => '5']))->toThrow(DrawFailure::class);
});
