<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum TeamType: string
{
    use EnumToArray;

    case COLLEGE = 'College';
    case PROFESSIONAL = 'Professional';
}