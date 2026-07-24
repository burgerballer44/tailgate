<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

enum UserStatus: string
{
    use EnumToArray;

    // This user can use the app normally.
    case ACTIVE = 'Active';

    // This user is registered but needs to confirm their email.
    case PENDING = 'Pending';

    // This user is deleted and cannot use the app.
    case DELETED = 'Deleted';
}
