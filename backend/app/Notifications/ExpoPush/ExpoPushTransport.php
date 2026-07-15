<?php

namespace App\Notifications\ExpoPush;

interface ExpoPushTransport
{
    public function send(ExpoPushMessage ...$messages): ExpoPushResult;

    /** @return array<string, ExpoPushReceipt> */
    public function receipts(string ...$ticketIds): array;
}
