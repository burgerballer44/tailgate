<?php

namespace App\Services;

use App\DTO\ValidatedGameData;
use App\Models\Game;
use App\Services\Contracts\GameCommandInterface;

/**
 * Executes game lifecycle actions used by scheduling and scoring management.
 * Centralizes create, update, and delete behavior for game persistence.
 */
class GameCommandService implements GameCommandInterface
{
    /**
     * Persists a new game using normalized season, team, score, and time inputs.
     *
     * @param  ValidatedGameData  $data  Validated game data including teams, scores, and start time.
     * @return Game The created game instance.
     */
    public function create(ValidatedGameData $data): Game
    {
        // Keep payload assembly explicit so persisted fields are easy to audit.
        $gameData = [
            'season_id' => $data->season_id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date_time' => $data->start_date_time,
            'start_time_tbd' => $data->start_time_tbd,
        ];

        // Persist and return the newly created game aggregate.
        return Game::create($gameData);
    }

    /**
     * Applies schedule and scoring changes to an existing game.
     *
     * @param  Game  $game  The game to update.
     * @param  ValidatedGameData  $data  Validated data to apply to the game.
     */
    public function update(Game $game, ValidatedGameData $data): void
    {
        // Build a full replacement payload from validated schedule/scoring values.
        $updateData = [
            'season_id' => $data->season_id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date_time' => $data->start_date_time,
            'start_time_tbd' => $data->start_time_tbd,
        ];

        // Persist the new state in one save call.
        $game->fill($updateData);
        $game->save();
    }

    /**
     * Removes a game record from persistence.
     *
     * @param  Game  $game  The game to delete.
     */
    public function delete(Game $game): void
    {
        // Delete by key to keep command behavior stateless and predictable.
        Game::destroy($game->getKey());
    }
}
