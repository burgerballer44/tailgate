<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

enum GroupRole: string
{
    use EnumToArray;

    /** Group administrator with elevated management permissions. */
    case GROUP_ADMIN = 'Group-Admin';

    /** Standard group member without administrative permissions. */
    case GROUP_MEMBER = 'Group-Member';
}
