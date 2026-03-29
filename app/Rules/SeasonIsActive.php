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

        if (! $season) {
            $fail('Season not found.');

            return;
        }

        if (! $season->active) {
            $fail('Season is not active.');

            return;
        }

        $today = (new \DateTime('today'))->format('Y-m-d');
        $seasonActive = $season->active_date->format('Y-m-d');
        $seasonInactive = $season->inactive_date->format('Y-m-d');

        if ($season->active_date && $today < $seasonActive) {
            $fail('Season has not started yet.');

            return;
        }

        if ($season->inactive_date && $today > $seasonInactive) {
            $fail('Season has ended.');

            return;
        }
    }
}
