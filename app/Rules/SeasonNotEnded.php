<?php
 
namespace App\Rules;
 
use App\Models\Season;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
 
class SeasonNotEnded implements ValidationRule
{

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $season = Season::where('id', $value)->first();

        $today = (new \DateTime('today'))->format('Y-m-d');
        $seasonEnd = \DateTimeImmutable::createFromFormat("Y-m-d", $season->season_end)->format("Y-m-d");

        if ($today > $seasonEnd) {
            $fail('Season has ended.');
        }

    }
}