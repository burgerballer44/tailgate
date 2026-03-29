<?php

namespace App\Services;

use App\Models\Game;
use App\DTO\ValidatedGameData;
use App\Services\Contracts\GameCommandInterface;

class GameCommandService implements GameCommandInterface
{
    /**
     * Create a new game with the provided data.
     * This method handles game creation logic, including setting season, teams, scores, and date/time.
     *
     * @param  ValidatedGameData  $data  Validated game data including season, teams, scores, start_date, start_time.
     * @return Game The created game instance.
     */
    public function create(ValidatedGameData $data): Game
    {
        $gameData = [
            'season_id' => $data->season_id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date' => (string) $data->start_date,
            'start_time' => (string) $data->start_time,
        ];

        return Game::create($gameData);
    }

    /**
     * Update an existing game's information in the system.
     * This method is used to modify game details such as scores or date/time.
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
            'start_date' => (string) $data->start_date,
            'start_time' => (string) $data->start_time,
        ];

        $game->fill($updateData);
        $game->save();
    }

    /**
     * Delete a game from the system.
     * This method permanently removes the game.
     *
     * @param  Game  $game  The game to delete.
     */
    public function delete(Game $game): void
    {
        $game->delete();
    }
}