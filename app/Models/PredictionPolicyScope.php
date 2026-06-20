<?php

namespace App\Models;

enum PredictionPolicyScope: string
{
    case APP = 'app';
    case GROUP = 'group';
}