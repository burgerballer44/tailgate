<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

enum TeamType: string
{
    use EnumToArray;

    case COLLEGE = 'College';
    case PROFESSIONAL = 'Professional';
}
