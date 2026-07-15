<?php

namespace App\Notifications\ExpoPush;

final readonly class ExpoPushResult
{
    /**
     * @param  array<int, string>  $invalidTokens
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public int $acceptedCount,
        public array $invalidTokens = [],
        public array $errors = [],
    ) {}
}
