<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces unique player usernames within a single group namespace.
 * Prevents duplicate names that would create ambiguity in group rosters.
 */
class UniqueUsernamePerGroup implements ValidationRule
{
    /**
     * Validates that the requested player name is not already used in this group.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');

        if ($group->players->pluck('player_name')->contains($value)) {
            $fail('Please choose a unique username for this group. This username is unavailable.');
        }
    }
}
