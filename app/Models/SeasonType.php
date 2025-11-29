<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum SeasonType: string
{
    use EnumToArray;

    case PRE = 'Preseason';
    case REGULAR = 'Regular Season';
    case POST = 'Postseason';
}
