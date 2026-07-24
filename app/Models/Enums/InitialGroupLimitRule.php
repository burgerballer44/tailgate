<?php

namespace App\Models\Enums;

use App\Traits\EnumToArray;

/**
 * Defines default and context-specific player/member/follow limits for groups.
 */
enum InitialGroupLimitRule
{
    use EnumToArray;

    /** Default member limit assigned when a group is created. */
    case MEMBER_LIMIT;

    /** Default group-level player limit assigned when a group is created. */
    case PLAYER_LIMIT;

    /** Player limit for regular self-service member flows. */
    case MEMBER_PLAYER_LIMIT;

    /** Default follow limit assigned when a group is created. */
    case FOLLOW_LIMIT;

    /**
     * Resolve the integer limit for the selected rule.
     */
    public function value(): int
    {
        return match ($this) {
            self::MEMBER_LIMIT => 10,
            self::PLAYER_LIMIT => 1,
            self::MEMBER_PLAYER_LIMIT => 1,
            self::FOLLOW_LIMIT => 1,
        };
    }
}