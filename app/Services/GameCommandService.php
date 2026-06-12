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
     * @param  ValidatedGameData  $data  Validated game data including season, teams, scores, start date-time, and TBD flag.
     * @return Game  The created game instance.
     */
    public function create(ValidatedGameData $data): Game
    {
        $gameData = [
            'season_id' => $data->season_id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date_time' => $data->start_date_time,
            'start_time_tbd' => $data->start_time_tbd,
        ];

        return Game::create($gameData);
    }

    /**
     * Applies schedule and scoring changes to an existing game.
     *
     * @param  Game  $game  The game to update.
     * @param  ValidatedGameData  $data  Validated data to update the game with.
     */
    public function update(Game $game, ValidatedGameData $data): void
    {
        $updateData = [
            'season_id' => $data->season_id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date_time' => $data->start_date_time,
            'start_time_tbd' => $data->start_time_tbd,
        ];

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
        $game->delete();
    }
}
