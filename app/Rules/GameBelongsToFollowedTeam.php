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
     *
     * Resolves the group from the current route and checks whether the game's home or
     * away team is in the group's followed-team list, with optional sport-scoped filtering.
     * Uses already-loaded follows when available to avoid redundant queries.
     *
     * @param string $attribute The dot-notation field name being validated.
     * @param mixed $value The game ID being validated.
     * @param Closure(string): void $fail Closure invoked with an error message if validation fails.
     * @return void
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
