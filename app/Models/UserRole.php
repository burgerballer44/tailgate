<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum UserRole: string
{
    use EnumToArray;

    /** Standard user account with normal access. */
    case REGULAR = 'Regular';

    /** Elevated internal account used for developer-only workflows. */
    case DEVELOPER = 'Developer';
}
