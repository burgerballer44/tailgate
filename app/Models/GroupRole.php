<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum GroupRole: string
{
    use EnumToArray;

    case GROUP_ADMIN = 'Group-Admin';
    case GROUP_MEMBER = 'Group-Member';
}
