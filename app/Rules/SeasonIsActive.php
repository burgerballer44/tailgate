<?php

namespace App\Rules;

use App\Models\Season;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Restricts season-bound operations to existing, active seasons.
 * Rejects requests targeting missing or inactive seasons.
 */
class SeasonIsActive implements ValidationRule
{
    /**
     * Validates that the selected season exists and is currently active.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $season = Season::query()->find($value);

        if (! $season) {
            $fail('Season not found.');

            return;
        }

        if (! $season->active) {
            $fail('Season is not active.');

            return;
        }
    }
}
