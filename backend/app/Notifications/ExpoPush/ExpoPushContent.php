<?php

namespace App\Notifications\ExpoPush;

final readonly class ExpoPushContent
{
    /** @param array<string, string|int|float|bool|null> $data */
    public function __construct(
        public string $title,
        public string $body,
        public array $data,
        public int $badge,
        public string $channelId = 'general',
    ) {}
}
