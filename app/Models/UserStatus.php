<?php

namespace App\Models;

enum UserStatus: string
{
    case ACTIVE = 'Active'; // can use the app
    case PENDING = 'Pending'; // registered but needs to confirm email
    case DELETED = 'Deleted'; // is deleted
}
