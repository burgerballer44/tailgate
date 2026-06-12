<?php

namespace App\Services;

use App\DTO\ValidatedGameData;
use App\DTO\ValidatedSeasonData;
use App\Models\Game;
use App\Models\Season;
use App\Services\Contracts\GameCommandInterface;
use App\Services\Contracts\SeasonCommandInterface;

/**
 * Executes season lifecycle actions, including persistence of season metadata and related games.
 * Centralizes season creation and update behavior for consistent league management.
 */
class SeasonCommandService implements SeasonCommandInterface
{
    public function __construct(
        private GameCommandInterface $gameCommandService
    ) {}

    /**
     * Persists a new season with normalized sport, type, and activation state.
     *
     * @param  ValidatedSeasonData  $data  Validated season data including name, sport, season_type, and active state.
     * @return Season  The created season instance.
     */
    public function create(ValidatedSeasonData $data): Season
    {
        $seasonData = [
            'name' => $data->name,
            'sport' => $data->sport->value,
            'season_type' => $data->season_type->value,
            'active' => $data->active ?? false,
        ];

        return Season::create($seasonData);
    }

    /**
     * Applies metadata and activation changes to an existing season.
     *
     * @param  Season  $season  The season to update.
     * @param  ValidatedSeasonData  $data  Validated data to update the season with.
     * @return Season  The updated season instance.
     */
    public function update(Season $season, ValidatedSeasonData $data): Season
    {
        $updateData = [
            'name' => $data->name,
            'sport' => $data->sport->value,
            'season_type' => $data->season_type->value,
            'active' => $data->active ?? false,
        ];

        $season->fill($updateData);
        $season->save();

        return $season;
    }

    /**
     * Removes a season record from persistence.
     *
     * @param  Season  $season  The season to delete.
     */
    public function delete(Season $season): void
    {
        Season::destroy($season->getKey());
    }

    /**
     * Creates and attaches a new game within the provided season context.
     *
     * @param  Season  $season  The season to add the game to.
     * @param  ValidatedGameData  $data  Validated game data including teams, scores, start date-time, and TBD flag.
     * @return Game  The created game instance.
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
            'start_date_time' => $data->start_date_time,
            'start_time_tbd' => $data->start_time_tbd,
        ]);

        return $this->gameCommandService->create($gameData);
    }
}
