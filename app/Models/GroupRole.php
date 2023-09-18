<?php

namespace App\Models;

enum GroupRole: string
{
    case GROUP_ADMIN = 'Group-Admin';
    case GROUP_MEMBER = 'Group-Member';
}