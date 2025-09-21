<?php

namespace App\Models;

use App\Helpers\EnumToArray;

enum SeasonType: string
{
    use EnumToArray;

    case REGULAR = 'Regular Season';
    case POST = 'Post Season';
}
