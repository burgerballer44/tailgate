<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

enum Sport: string
{
    use EnumToArray;

    case BASKETBALL = 'Basketball';
    case FOOTBALL = 'Football';

    /**
     * Get the icon used to represent the sport in compact UI surfaces.
     *
     * @return HtmlEntity The HTML entity mapped to the sport.
     */
    public function htmlEntity(): HtmlEntity
    {
        return match ($this) {
            self::BASKETBALL => HtmlEntity::BASKETBALL,
            self::FOOTBALL => HtmlEntity::FOOTBALL,
        };
    }
}
