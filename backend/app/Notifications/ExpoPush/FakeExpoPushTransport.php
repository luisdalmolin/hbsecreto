<?php

namespace App\Notifications\ExpoPush;

final class FakeExpoPushTransport implements ExpoPushTransport
{
    /** @var array<int, ExpoPushMessage> */
    private array $messages = [];

    public function send(ExpoPushMessage ...$messages): ExpoPushResult
    {
        array_push($this->messages, ...$messages);

        return new ExpoPushResult(count($messages));
    }

    /** @return array<int, ExpoPushMessage> */
    public function messages(): array
    {
        return $this->messages;
    }
}
