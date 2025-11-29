<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum Sport: string
{
    use EnumToArray;

    case BASKETBALL = 'Basketball';
    case FOOTBALL = 'Football';
}
