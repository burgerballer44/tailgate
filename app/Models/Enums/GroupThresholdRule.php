<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

/**
 * Defines fixed threshold-style rules used in group governance and creation.
 */
enum GroupThresholdRule
{
    use EnumToArray;
    
    // Invite code character length generated for new groups.
    case INVITE_CODE_LENGTH;

    // Minimum number of admins that must remain assigned to a group.
    case MIN_NUMBER_ADMINS;

    /**
     * Resolve the integer threshold for the selected rule.
     */
    public function value(): int
    {
        return match ($this) {
            self::INVITE_CODE_LENGTH => 10,
            self::MIN_NUMBER_ADMINS => 1,
        };
    }
}