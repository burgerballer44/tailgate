<?php

namespace App\Services\Contracts;

use App\Models\Game;
use App\Models\Group;
use App\Models\Player;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Defines read operations for prediction records.
 */
interface PredictionQueryInterface
{
    /**
     * Load predictions scoped to the provided players and games.
     *
     * @param  Collection<int, Player>  $players
     * @param  Collection<int, Game>  $games
     * @return Collection<int, Prediction>
     */
    public function getPredictionsForPlayersAndGames(Collection $players, Collection $games): Collection;

    /**
     * Build a prediction query for a group-wide listing.
     *
     * @param  Group  $group  The group whose player predictions should be loaded.
     * @return Builder The filtered prediction query.
     */
    public function getPredictionsForGroup(Group $group): Builder;

    /**
     * Build a prediction query for a single player.
     *
     * @param  Player  $player  The player whose predictions should be loaded.
     * @return Builder The filtered prediction query.
     */
    public function getPredictionsForPlayer(Player $player): Builder;
}
