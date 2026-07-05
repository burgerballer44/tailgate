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
    /**
     * Create a season coordinator that delegates game creation.
     *
     * @param  GameCommandInterface  $gameCommandService  The service used to create games inside a season.
     */
    public function __construct(
        private GameCommandInterface $gameCommandService
    ) {}

    /**
     * Persists a new season with normalized sport, type, and activation state.
     *
     * @param  ValidatedSeasonData  $data  Validated season data including identity and activation state.
     * @return Season The created season instance.
     */
    public function create(ValidatedSeasonData $data): Season
    {
        // Keep season payload assembly explicit for predictable persistence.
        $seasonData = [
            'name' => $data->name,
            'sport' => $data->sport->value,
            'season_type' => $data->season_type->value,
            'active' => $data->active ?? false,
        ];

        // Persist and return the new season aggregate.
        return Season::create($seasonData);
    }

    /**
     * Applies metadata and activation changes to an existing season.
     *
     * @param  Season  $season  The season to update.
     * @param  ValidatedSeasonData  $data  Validated data to apply to the season.
     * @return Season The updated season instance.
     */
    public function update(Season $season, ValidatedSeasonData $data): Season
    {
        // Build a full update payload from validated season state.
        $updateData = [
            'name' => $data->name,
            'sport' => $data->sport->value,
            'season_type' => $data->season_type->value,
            'active' => $data->active ?? false,
        ];

        // Persist all modified season fields together.
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
        // Delete by key to keep deletion behavior idempotent for callers.
        Season::destroy($season->getKey());
    }

    /**
     * Creates and attaches a new game within the provided season context.
     *
     * @param  Season  $season  The season to add the game to.
     * @param  ValidatedGameData  $data  Validated game data including teams, scores, and start time.
     * @return Game The created game instance.
     */
    public function addGame(Season $season, ValidatedGameData $data): Game
    {
        // Compose a game DTO that is guaranteed to reference the target season.
        $gameData = ValidatedGameData::fromArray([
            'season_id' => $season->id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date_time' => $data->start_date_time,
            'start_time_tbd' => $data->start_time_tbd,
        ]);

        // Delegate creation to the game command service for single-write-path consistency.
        return $this->gameCommandService->create($gameData);
    }
}
