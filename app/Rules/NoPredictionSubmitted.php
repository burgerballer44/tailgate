<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Prevents duplicate prediction submissions for the same player-game pair.
 * Ensures each player can submit only one prediction per game.
 */
class NoPredictionSubmitted implements ValidationRule
{
    /**
     * Validates that no existing prediction has already been submitted for this player and game.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $player = request()->route('player');

        if ($player->predictions()->where('game_id', $value)->exists()) {
            $fail('A prediction has already been submitted for this game by this player.');
        }
    }
}