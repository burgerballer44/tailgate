<?php

namespace App\Models;

enum UserRole: string
{
    case REGULAR = 'Regular'; // the average user, normal people who sign up
    case ADMIN = 'Admin'; // an important person who can do whatever
}
