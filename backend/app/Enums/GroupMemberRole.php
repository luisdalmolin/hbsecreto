<?php

namespace App\Enums;

enum GroupMemberRole: string
{
    case Admin = 'admin';
    case Member = 'member';
}
