<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case ConversationMessages = 'conversation-messages';
    case EditionUpdates = 'edition-updates';
    case System = 'system';
}
