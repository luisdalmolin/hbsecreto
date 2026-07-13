<?php

namespace App\Draws;

use App\Enums\DrawConstraintType;

final class ClassicDrawAlgorithm implements DrawAlgorithm
{
    private const int ForbiddenCost = 1_000_000_000;

    public function draw(DrawInput $input): DrawResult
    {
        $participants = $input->participants;
        $size = count($participants);

        if ($size < 2) {
            throw new DrawFailure(DrawFailureCode::InvalidParticipantCount);
        }

        $indexById = [];
        $memberByIndex = [];

        foreach ($participants as $index => $participant) {
            if (isset($indexById[$participant->id])) {
                throw new DrawFailure(DrawFailureCode::InvalidConstraint);
            }

            $indexById[$participant->id] = $index;
            $memberByIndex[$index] = $participant->groupMemberId;
        }

        $baseCosts = array_fill(0, $size, array_fill(0, $size, 0));

        foreach ($baseCosts as $giverIndex => &$row) {
            $row[$giverIndex] = self::ForbiddenCost;
        }
        unset($row);

        foreach ($input->history as $historicalPair) {
            $giverIndex = array_search($historicalPair->giverGroupMemberId, $memberByIndex, true);
            $receiverIndex = array_search($historicalPair->receiverGroupMemberId, $memberByIndex, true);

            if ($giverIndex !== false && $receiverIndex !== false) {
                $baseCosts[$giverIndex][$receiverIndex] += $historicalPair->penalty;
            }
        }

        $forcedReceivers = [];
        $forcedGivers = [];

        foreach ($input->constraints as $constraint) {
            $giverIndex = $indexById[$constraint->giverParticipantId] ?? null;
            $receiverIndex = $indexById[$constraint->receiverParticipantId] ?? null;

            if ($giverIndex === null || $receiverIndex === null || $giverIndex === $receiverIndex) {
                throw new DrawFailure(DrawFailureCode::InvalidConstraint);
            }

            if ($constraint->type === DrawConstraintType::MustNotPair) {
                if (($forcedReceivers[$giverIndex] ?? null) === $receiverIndex || ($forcedReceivers[$receiverIndex] ?? null) === $giverIndex) {
                    throw new DrawFailure(DrawFailureCode::ConflictingConstraints);
                }

                $baseCosts[$giverIndex][$receiverIndex] = self::ForbiddenCost;
                $baseCosts[$receiverIndex][$giverIndex] = self::ForbiddenCost;

                continue;
            }

            if ((isset($forcedReceivers[$giverIndex]) && $forcedReceivers[$giverIndex] !== $receiverIndex)
                || (isset($forcedGivers[$receiverIndex]) && $forcedGivers[$receiverIndex] !== $giverIndex)
                || $baseCosts[$giverIndex][$receiverIndex] >= self::ForbiddenCost) {
                throw new DrawFailure(DrawFailureCode::ConflictingConstraints);
            }

            $forcedReceivers[$giverIndex] = $receiverIndex;
            $forcedGivers[$receiverIndex] = $giverIndex;
        }

        foreach ($forcedReceivers as $giverIndex => $receiverIndex) {
            foreach (array_keys($baseCosts[$giverIndex]) as $candidateReceiver) {
                if ($candidateReceiver !== $receiverIndex) {
                    $baseCosts[$giverIndex][$candidateReceiver] = self::ForbiddenCost;
                }
            }

            foreach (array_keys($baseCosts) as $candidateGiver) {
                if ($candidateGiver !== $giverIndex) {
                    $baseCosts[$candidateGiver][$receiverIndex] = self::ForbiddenCost;
                }
            }
        }

        $scaledCosts = [];
        $tieScale = ($size * $size * $size) + 1;
        $maximumBaseCost = 0;

        foreach ($baseCosts as $row) {
            foreach ($row as $baseCost) {
                if ($baseCost < self::ForbiddenCost) {
                    $maximumBaseCost = max($maximumBaseCost, $baseCost);
                }
            }
        }

        $forbiddenScaledCost = ((($maximumBaseCost * $tieScale) + ($size * $size) + 1) * $size) + 1;

        foreach ($baseCosts as $giverIndex => $row) {
            foreach ($row as $receiverIndex => $baseCost) {
                $scaledCosts[$giverIndex][$receiverIndex] = $baseCost >= self::ForbiddenCost
                    ? $forbiddenScaledCost
                    : ($baseCost * $tieScale) + $this->tieCost($input->seed, $giverIndex, $receiverIndex, $size);
            }
        }

        $matching = $this->minimumCostPerfectMatching(array_map(array_values(...), array_values($scaledCosts)));
        $pairs = [];
        $totalPenalty = 0;

        foreach ($matching as $giverIndex => $receiverIndex) {
            if ($baseCosts[$giverIndex][$receiverIndex] >= self::ForbiddenCost) {
                throw new DrawFailure(DrawFailureCode::NoValidAssignment);
            }

            $pairs[] = new DrawPair($participants[$giverIndex]->id, $participants[$receiverIndex]->id);
            $totalPenalty += $baseCosts[$giverIndex][$receiverIndex];
        }

        return new DrawResult($pairs, $totalPenalty);
    }

    private function tieCost(int $seed, int $giverIndex, int $receiverIndex, int $size): int
    {
        $hash = hash('sha256', $seed.':'.$giverIndex.':'.$receiverIndex);

        return hexdec(substr($hash, 0, 7)) % ($size * $size);
    }

    /**
     * Hungarian algorithm for a square matrix.
     *
     * @param  list<list<int>>  $costs
     * @return list<int>
     */
    private function minimumCostPerfectMatching(array $costs): array
    {
        $size = count($costs);
        $rowPotential = array_fill(0, $size + 1, 0);
        $columnPotential = array_fill(0, $size + 1, 0);
        $matchedRow = array_fill(0, $size + 1, 0);
        $previousColumn = array_fill(0, $size + 1, 0);

        for ($row = 1; $row <= $size; $row++) {
            $matchedRow[0] = $row;
            $column = 0;
            $minimum = array_fill(0, $size + 1, PHP_INT_MAX);
            $used = array_fill(0, $size + 1, false);

            do {
                $used[$column] = true;
                $currentRow = $matchedRow[$column];
                $delta = PHP_INT_MAX;
                $nextColumn = 0;

                for ($candidate = 1; $candidate <= $size; $candidate++) {
                    if ($used[$candidate]) {
                        continue;
                    }

                    $reducedCost = $costs[$currentRow - 1][$candidate - 1] - $rowPotential[$currentRow] - $columnPotential[$candidate];

                    if ($reducedCost < $minimum[$candidate]) {
                        $minimum[$candidate] = $reducedCost;
                        $previousColumn[$candidate] = $column;
                    }

                    if ($minimum[$candidate] < $delta) {
                        $delta = $minimum[$candidate];
                        $nextColumn = $candidate;
                    }
                }

                for ($candidate = 0; $candidate <= $size; $candidate++) {
                    if ($used[$candidate]) {
                        $rowPotential[$matchedRow[$candidate]] += $delta;
                        $columnPotential[$candidate] -= $delta;
                    } else {
                        $minimum[$candidate] -= $delta;
                    }
                }

                $column = $nextColumn;
            } while ($matchedRow[$column] !== 0);

            do {
                $nextColumn = $previousColumn[$column];
                $matchedRow[$column] = $matchedRow[$nextColumn];
                $column = $nextColumn;
            } while ($column !== 0);
        }

        $matching = [];

        for ($row = 1; $row <= $size; $row++) {
            for ($column = 1; $column <= $size; $column++) {
                if ($matchedRow[$column] === $row) {
                    $matching[] = $column - 1;

                    continue 2;
                }
            }

            throw new DrawFailure(DrawFailureCode::NoValidAssignment);
        }

        return $matching;
    }
}
