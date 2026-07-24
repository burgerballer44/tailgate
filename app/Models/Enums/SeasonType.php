<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

enum SeasonType: string
{
    use EnumToArray;

    case REGULAR = 'Regular Season';
}
