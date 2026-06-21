<?php

namespace App\Models;

enum PredictionPolicyScope: string
{
    /** Policy is enforced globally across the application. */
    case APP = 'app';

    /** Policy is enabled or disabled per group. */
    case GROUP = 'group';
}