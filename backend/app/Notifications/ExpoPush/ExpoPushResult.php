<?php

namespace App\Notifications\ExpoPush;

final readonly class ExpoPushResult
{
    /** @param array<int, ExpoPushTicket> $tickets */
    public function __construct(public array $tickets) {}

    public function acceptedCount(): int
    {
        return count(array_filter($this->tickets, fn (ExpoPushTicket $ticket): bool => $ticket->accepted));
    }

    /** @return array<int, string> */
    public function invalidTokens(): array
    {
        return array_values(array_unique(array_map(
            fn (ExpoPushTicket $ticket): string => $ticket->token,
            array_filter($this->tickets, fn (ExpoPushTicket $ticket): bool => $ticket->errorCode === 'DeviceNotRegistered'),
        )));
    }
}
