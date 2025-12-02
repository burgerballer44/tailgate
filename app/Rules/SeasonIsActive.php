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
        $season = Season::where('id', $value)->first();

        if (!$season) {
            $fail('Season not found.');
            return;
        }

        if (!$season->active) {
            $fail('Season is not active.');
            return;
        }

        $today = (new \DateTime('today'))->format('Y-m-d');

        if ($season->active_date && $today < $season->active_date) {
            $fail('Season has not started yet.');
            return;
        }

        if ($season->inactive_date && $today > $season->inactive_date) {
            $fail('Season has ended.');
            return;
        }
    }
}
