<?php
 
namespace App\Rules;
 
use App\Models\Game;
use App\Models\Group;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
 
class NoScoreSubmitted implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $player = request()->route('player');
        
        if ($player->scores()->where('game_id', $value)->exists()) {
            $fail('A score has already been submitted for this game by this player.');
        }
    }
}