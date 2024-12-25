<?php
 
namespace App\Rules;
 
use App\Models\Group;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
 
class UniqueUsernamePerGroup implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');

        if ($group->players->pluck('player_name')->contains($value)) {
            $fail('Please choose a unique username for this group. This username is unavailable.');
        }
    }
}