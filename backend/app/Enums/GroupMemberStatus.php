<?php

namespace App\Enums;

enum GroupMemberStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Inactive = 'inactive';
}
