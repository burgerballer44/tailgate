<?php

namespace App\Rules;

use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Prevents prediction submissions for games outside a group's followed teams.
 * Applies optional sport-scoped follow constraints when validating eligible games.
 */
class GameBelongsToFollowedTeam implements ValidationRule
{
    /**
     * Validates that the selected game is eligible for this group's follow configuration.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $route = request()->route();
        $group = is_object($route) ? $route->parameter('group') : null;
        $game = Game::query()->with('season')->find($value);

        if (! $group instanceof Group || ! $game) {
            $fail('Cannot submit a prediction for a game in a team you are not following.');

            return;
        }

        $follows = $group->relationLoaded('follows')
            ? $group->follows
            : $group->follows()->get();

        if ($follows->isEmpty()) {
            $fail('Cannot submit a prediction for a game in a team you are not following.');

            return;
        }

        $matchesAnyFollow = $follows->contains(
            fn (Follow $follow) => $this->gameTeamIsFollowed($game, $follow)
        );

        if (! $matchesAnyFollow) {
            $fail('Cannot submit a prediction for a game in a team you are not following.');
        }
    }

    /**
     * Evaluates whether the game matches a followed team, including optional sport scoping.
     */
    private function gameTeamIsFollowed(Game $game, Follow $follow): bool
    {
        $containsFollowedTeam = $game->home_team_id === $follow->team_id || $game->away_team_id === $follow->team_id;

        if (! $containsFollowedTeam) {
            return false;
        }

        if (! $follow->sport) {
            return true;
        }

        return $game->season?->sport === $follow->sport->value;
    }
}
