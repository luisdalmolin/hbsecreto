<?php

namespace App\Draws;

use RuntimeException;

final class DrawFailure extends RuntimeException
{
    public function __construct(public readonly DrawFailureCode $failureCode)
    {
        parent::__construct($failureCode->value);
    }
}
