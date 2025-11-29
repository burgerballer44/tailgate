<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum SeasonType: string
{
    use EnumToArray;

    case REGULAR = 'Regular Season';
    case POST = 'Post Season';
}
