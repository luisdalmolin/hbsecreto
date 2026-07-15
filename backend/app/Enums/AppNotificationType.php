<?php

namespace App\Enums;

enum AppNotificationType: string
{
    case ConversationMessage = 'conversation-message';
    case EditionDrawn = 'edition-drawn';
    case EditionRevealed = 'edition-revealed';
    case PushDiagnostic = 'push-diagnostic';
}
