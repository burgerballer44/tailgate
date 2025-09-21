<?php

namespace App\Models;

use App\Helpers\EnumToArray;

enum UserStatus: string
{
    use EnumToArray;

    case ACTIVE = 'Active'; // can use the app
    case PENDING = 'Pending'; // registered but needs to confirm email
    case DELETED = 'Deleted'; // is deleted
}
