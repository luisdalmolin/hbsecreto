<?php

namespace App\Draws;

enum DrawFailureCode: string
{
    case InvalidParticipantCount = 'invalid_participant_count';
    case InvalidConstraint = 'invalid_constraint';
    case ConflictingConstraints = 'conflicting_constraints';
    case NoValidAssignment = 'no_valid_assignment';
    case InvalidEditionState = 'invalid_edition_state';
    case CorruptAssignments = 'corrupt_assignments';
    case AssignmentNotAvailable = 'assignment_not_available';
    case AssignmentsNotRevealed = 'assignments_not_revealed';
    case PendingPayment = 'pending_payment';
}
