<?php

namespace App\Notifications\ExpoPush;

final readonly class ExpoPushTicket
{
    public function __construct(
        public string $token,
        public bool $accepted,
        public ?string $ticketId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
