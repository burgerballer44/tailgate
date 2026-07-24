<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

/**
 * Canonical fallback values used when upstream team data is missing.
 */
enum TeamFallback: string
{
    use EnumToArray;
    
    // Placeholder organization name used when source data is incomplete.
    case ORGANIZATION = 'Unknown Team';

    // Placeholder conference name used when source data is incomplete.
    case CONFERENCE = 'Unknown Conference';

    /**
     * Resolve the fallback string value for the selected case.
     */
    public function value(): string
    {
        return match ($this) {
            self::ORGANIZATION => self::ORGANIZATION->value,
            self::CONFERENCE => self::CONFERENCE->value,
        };
    }
}