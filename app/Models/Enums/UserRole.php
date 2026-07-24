<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

enum UserRole: string
{
    use EnumToArray;

    // Standard user account with normal access.
    case REGULAR = 'Regular';

    // Elevated internal account used for developers.
    case DEVELOPER = 'Developer';
}
