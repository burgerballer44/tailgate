<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Player;
use App\Models\Prediction;
use App\Services\Contracts\PredictionQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Supports prediction retrieval for read-only workflows.
 */
class PredictionQueryService implements PredictionQueryInterface
{
    /**
     * Load predictions scoped to the provided players and games.
     *
     * @param Collection<int, \\App\\Models\\Player> $players
     * @param Collection<int, \\App\\Models\\Game> $games
     * @return Collection<int, Prediction>
     */
    public function getPredictionsForPlayersAndGames(Collection $players, Collection $games): Collection
    {
        // Short-circuit when either side of the relation filter is empty.
        if ($players->isEmpty() || $games->isEmpty()) {
            return collect();
        }

        // Restrict predictions to the requested players and games.
        return Prediction::query()
            ->whereIn('player_id', $players->pluck('id'))
            ->whereIn('game_id', $games->pluck('id'))
            ->get();
    }

    /**
     * Build a prediction query for a group-wide listing.
     *
     * @param  Group  $group  The group whose player predictions should be loaded.
     * @return Builder The filtered prediction query.
     */
    public function getPredictionsForGroup(Group $group): Builder
    {
        // Scope predictions to players whose memberships belong to this group.
        return Prediction::query()
            ->whereHas('player.member', fn (Builder $query) => $query->where('group_id', $group->id))
            ->with([
                'player.member.user',
                'game.homeTeam',
                'game.awayTeam',
            ])
            ->latest();
    }

    /**
     * Build a prediction query for a single player.
     *
     * @param  Player  $player  The player whose predictions should be loaded.
     * @return Builder The filtered prediction query.
     */
    public function getPredictionsForPlayer(Player $player): Builder
    {
        // Restrict to a single player's predictions with game context eager loaded.
        return Prediction::query()
            ->where('player_id', $player->id)
            ->with([
                'game.homeTeam',
                'game.awayTeam',
            ])
            ->latest();
    }
}
