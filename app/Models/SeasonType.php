<?php

namespace App\Models;

enum SeasonType: string
{
    case REGULAR = 'Regular Season';
    case POST = 'Post Season';
}