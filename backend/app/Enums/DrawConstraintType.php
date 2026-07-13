<?php

namespace App\Enums;

enum DrawConstraintType: string
{
    case MustNotPair = 'must_not_pair';
    case MustPair = 'must_pair';
}
