<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\DTO\ValidatedGameData;
use Illuminate\Contracts\Database\Eloquent\Builder;

class GameService
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

    /**
     * Filter games based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $filters  An associative array of query parameters to filter games.
     * @return Builder A query builder instance for the filtered games.
     */
    public function query(array $filters)
    {
        $query = Game::query();

        if (isset($filters['season_id'])) {
            $query->where('season_id', $filters['season_id']);
        }

        return $query;
    }

    /**
     * Get available teams for a season based on the season's sport.
     * This method retrieves teams that participate in the same sport as the season, used for game creation and editing.
     *
     * @param  Season  $season  The season to get available teams for.
     * @return array An associative array of team full names keyed by team ID.
     */
    public function getAvailableTeamsForSeason(Season $season): array
    {
        return Team::whereHas('sports', function ($query) use ($season) {
            $query->where('sport', $season->sport);
        })->get()->pluck('full_name', 'id')->toArray();
    }
}