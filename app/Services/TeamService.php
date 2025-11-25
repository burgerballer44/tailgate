<?php

namespace App\Services;

use App\Models\Team;
use App\DTO\ValidatedTeamData;
use Illuminate\Contracts\Database\Eloquent\Builder;

class TeamService
{
    /**
     * Create a new team with the provided data.
     * This method handles team creation logic, including setting designation, mascot, and sport.
     *
     * @param  ValidatedTeamData  $data  Validated team data including designation, mascot, sport.
     * @return Team The created team instance.
     */
    public function create(ValidatedTeamData $data): Team
    {
        $teamData = [
            'designation' => $data->designation,
            'mascot' => $data->mascot,
            'sport' => $data->sport->value,
        ];

        return Team::create($teamData);
    }

    /**
     * Update an existing team's information in the system.
     * This method is used to modify team details such as designation, mascot, or sport.
     *
     * @param  Team  $team  The team to update.
     * @param  ValidatedTeamData  $data  Validated data to update the team with.
     */
    public function update(Team $team, ValidatedTeamData $data): void
    {
        // Team data properties are never expected to be null or set to null.
        // The $extra array can contain any additional fields to update including null values.

        // remove null values
        $updateData = array_filter([
            'designation' => $data->designation,
            'mascot' => $data->mascot,
            'sport' => $data->sport?->value,
        ], static fn ($value) => $value !== null);

        $team->fill($updateData);
        $team->save();
    }

    /**
     * Delete a team from the system.
     * This method permanently removes the team.
     *
     * @param  Team  $team  The team to delete.
     */
    public function delete(Team $team): void
    {
        $team->delete();
    }

    /**
     * Filter teams based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter teams.
     * @return Builder A query builder instance for the filtered teams.
     */
    public function query(array $query)
    {
        return Team::filter($query);
    }
}