<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Season;
use App\Models\Sport;
use App\Models\SeasonType;
use App\Services\GameService;
use App\DTO\ValidatedGameData;
use App\DTO\ValidatedSeasonData;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SeasonService
{
    public function __construct(
        private GameService $gameService
    ) {}

    /**
     * Create a new season with the provided data.
     * This method handles season creation logic, including setting name, sport, season_type, and dates.
     *
     * @param  ValidatedSeasonData  $data  Validated season data including name, sport, season_type, season_start, season_end.
     * @return Season The created season instance.
     */
    public function create(ValidatedSeasonData $data): Season
    {
        $seasonData = [
            'name' => $data->name,
            'sport' => $data->sport->value,
            'season_type' => $data->season_type->value,
            'season_start' => (string) $data->season_start,
            'season_end' => (string) $data->season_end,
            'active' => $data->active ?? false,
            'active_date' => $data->active_date ? (string) $data->active_date : (string) $data->season_start,
            'inactive_date' => $data->inactive_date ? (string) $data->inactive_date : (string) $data->season_end,
        ];

        return Season::create($seasonData);
    }

    /**
     * Update an existing season's information in the system.
     * This method is used to modify season details such as name, sport, season_type, or dates.
     *
     * @param  Season  $season  The season to update.
     * @param  ValidatedSeasonData  $data  Validated data to update the season with.
     * @return Season The updated season instance.
     */
    public function update(Season $season, ValidatedSeasonData $data): Season
    {
        // Season data properties are never expected to be null or set to null.

        $updateData = [
            'name' => $data->name,
            'sport' => $data->sport->value,
            'season_type' => $data->season_type->value,
            'season_start' => (string) $data->season_start,
            'season_end' => (string) $data->season_end,
        ];

        if ($data->active !== null) {
            $updateData['active'] = $data->active;
        }

        if ($data->active_date !== null) {
            $updateData['active_date'] = (string) $data->active_date;
        }

        if ($data->inactive_date !== null) {
            $updateData['inactive_date'] = (string) $data->inactive_date;
        }

        $season->fill($updateData);
        $season->save();

        return $season;
    }

    /**
     * Delete a season from the system.
     * This method permanently removes the season.
     *
     * @param  Season  $season  The season to delete.
     */
    public function delete(Season $season): void
    {
        $season->delete();
    }

    /**
     * Add a game to a season.
     * This method creates a new game and associates it with the given season.
     *
     * @param  Season  $season  The season to add the game to.
     * @param  ValidatedGameData  $data  Validated game data including teams, scores, start_date, start_time.
     * @return Game The created game instance.
     */
    public function addGame(Season $season, ValidatedGameData $data): Game
    {
        // create new game data
        $gameData = ValidatedGameData::fromArray([
            'season_id' => $season->id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date' => $data->start_date,
            'start_time' => $data->start_time,
        ]);

        return $this->gameService->create($gameData);
    }

    /**
     * Filter seasons based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter seasons.
     * @return Builder A query builder instance for the filtered seasons.
     */
    public function query(array $query)
    {
        return Season::filter($query);
    }
}