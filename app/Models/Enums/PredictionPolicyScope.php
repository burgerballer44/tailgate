<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

enum PredictionPolicyScope: string
{
    use EnumToArray;

    // Policy is enforced globally across the application.
    case APP = 'app';

    // Policy is enabled or disabled per group.
    case GROUP = 'group';
}
