<?php

namespace App\Notifications\ExpoPush;

final class FakeExpoPushTransport implements ExpoPushTransport
{
    /** @var array<int, ExpoPushMessage> */
    private array $messages = [];

    /** @var array<string, ExpoPushReceipt> */
    private array $receipts = [];

    public function send(ExpoPushMessage ...$messages): ExpoPushResult
    {
        array_push($this->messages, ...$messages);

        $tickets = [];
        $ticketNumber = count($this->messages) - count($messages) + 1;

        foreach ($messages as $message) {
            $tickets[] = new ExpoPushTicket(
                token: $message->token,
                accepted: true,
                ticketId: 'fake-ticket-'.$ticketNumber,
            );
            $ticketNumber++;
        }

        return new ExpoPushResult($tickets);
    }

    public function receipts(string ...$ticketIds): array
    {
        return array_intersect_key($this->receipts, array_flip($ticketIds));
    }

    /** @return array<int, ExpoPushMessage> */
    public function messages(): array
    {
        return $this->messages;
    }

    public function addReceipt(ExpoPushReceipt $receipt): void
    {
        $this->receipts[$receipt->ticketId] = $receipt;
    }
}
