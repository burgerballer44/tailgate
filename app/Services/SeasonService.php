<?php

namespace App\Services;

use App\Models\Season;
use App\DTO\ValidatedSeasonData;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SeasonService
{
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
            'season_start' => $data->season_start,
            'season_end' => $data->season_end,
        ];

        return Season::create($seasonData);
    }

    /**
     * Update an existing season's information in the system.
     * This method is used to modify season details such as name, sport, season_type, or dates.
     *
     * @param  Season  $season  The season to update.
     * @param  ValidatedSeasonData  $data  Validated data to update the season with.
     */
    public function update(Season $season, ValidatedSeasonData $data): void
    {
        // Season data properties are never expected to be null or set to null.

        // remove null values
        $updateData = array_filter([
            'name' => $data->name,
            'sport' => $data->sport?->value,
            'season_type' => $data->season_type?->value,
            'season_start' => $data->season_start,
            'season_end' => $data->season_end,
        ], static fn ($value) => $value !== null);

        $season->fill($updateData);
        $season->save();
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