<?php

namespace App\Notifications\ExpoPush;

final readonly class ExpoPushReceipt
{
    public function __construct(
        public string $ticketId,
        public bool $delivered,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
