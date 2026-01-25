<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum UserRole: string
{
    use EnumToArray;

    case REGULAR = 'Regular'; // the average user, normal people who sign up
    case DEVELOPER = 'Developer'; // an important person who can do whatever
}
