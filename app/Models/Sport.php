<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum Sport: string
{
    use EnumToArray;

    case BASKETBALL = 'Basketball';
    case FOOTBALL = 'Football';

    public function htmlEntity(): HtmlEntity
    {
        return match ($this) {
            self::BASKETBALL => HtmlEntity::BASKETBALL,
            self::FOOTBALL => HtmlEntity::FOOTBALL,
        };
    }
}
