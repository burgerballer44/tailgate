<?php

namespace App\Rules;

use App\Models\Season;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SeasonIsActive implements ValidationRule
{
    /**
     * Run the validation rule.
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
