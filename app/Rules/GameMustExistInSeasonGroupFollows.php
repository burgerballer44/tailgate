<?php

namespace App\Rules;

use App\Models\Game;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GameMustExistInSeasonGroupFollows implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');
        $game = Game::where('id', $value)->first();

        if ($game->season_id != $group->follow->season_id) {
            $fail('Cannot submit a score for a game in a season you are not following.');
        }
    }
}
