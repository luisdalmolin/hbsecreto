<?php

namespace App\Draws;

final readonly class EditionTypeRegistry
{
    public function __construct(private ClassicDrawAlgorithm $classic) {}

    public function resolve(string $type): DrawAlgorithm
    {
        return match ($type) {
            'classic' => $this->classic,
            default => throw new DrawFailure(DrawFailureCode::InvalidEditionState),
        };
    }

    /** @param array<string, mixed> $settings */
    public function historyLookbackDepth(array $settings): int
    {
        $depth = $settings['historyLookbackDepth'] ?? 5;

        if (! is_int($depth) || $depth < 0 || $depth > 20) {
            throw new DrawFailure(DrawFailureCode::InvalidEditionState);
        }

        return $depth;
    }
}
