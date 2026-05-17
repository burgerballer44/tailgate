<?php

namespace App\Rules;

use App\Models\Game;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GameBelongsToFollowedTeam implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');
        $game = Game::where('id', $value)->first();

        if (! $group->follow || ! $this->gameTeamIsFollowed($game, $group->follow)) {
            $fail('Cannot submit a score for a game in a team you are not following.');
        }
    }

    /**
     * Check if the game belongs to the team the group is following.
     */
    private function gameTeamIsFollowed(Game $game, Follow $follow): bool
    {
        return $game->home_team_id === $follow->team_id || $game->away_team_id === $follow->team_id;
    }
}
