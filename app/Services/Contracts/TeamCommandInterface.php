<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedTeamData;
use App\Models\Team;

/**
 * Defines write operations for teams and their sport associations.
 */
interface TeamCommandInterface
{
    /**
     * Create a new team from validated input.
     *
     * @param  ValidatedTeamData  $data  The normalized team payload.
     * @return Team The created team instance.
     */
    public function create(ValidatedTeamData $data): Team;

    /**
     * Update an existing team.
     *
     * @param  Team  $team  The team to update.
     * @param  ValidatedTeamData  $data  The normalized team payload.
     * @return Team The updated team instance.
     */
    public function update(Team $team, ValidatedTeamData $data): Team;

    /**
     * Delete a team.
     *
     * @param  Team  $team  The team to delete.
     */
    public function delete(Team $team): void;
}
