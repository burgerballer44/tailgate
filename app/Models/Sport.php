<?php

namespace App\Models;

use App\Helpers\EnumToArray;

enum Sport: string
{
    use EnumToArray;

    case BASKETBALL = 'Basketball';
    case FOOTBALL = 'Football';
}
