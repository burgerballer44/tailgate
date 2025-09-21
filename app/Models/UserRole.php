<?php

namespace App\Models;

use App\Helpers\EnumToArray;

enum UserRole: string
{
    use EnumToArray;

    case REGULAR = 'Regular'; // the average user, normal people who sign up
    case ADMIN = 'Admin'; // an important person who can do whatever
}
