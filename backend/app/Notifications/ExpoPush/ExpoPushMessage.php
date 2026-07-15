<?php

namespace App\Notifications\ExpoPush;

final readonly class ExpoPushMessage
{
    public function __construct(
        public string $token,
        public ExpoPushContent $content,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'to' => $this->token,
            'title' => $this->content->title,
            'body' => $this->content->body,
            'data' => $this->content->data,
            'sound' => 'default',
            'channelId' => $this->content->channelId,
        ];
    }
}
