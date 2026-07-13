<?php

namespace App\Enums;

enum EditionStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Drawn = 'drawn';
    case Revealed = 'revealed';
    case Archived = 'archived';
}
