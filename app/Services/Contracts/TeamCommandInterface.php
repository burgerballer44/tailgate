<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedTeamData;
use App\Models\Team;

/**
 * Manages the complete lifecycle of team information and sport associations.
 * Handles team creation and updates (organization, type, conference, sports), and deletion,
 * supporting team directory and sports affiliation management.
 */
interface TeamCommandInterface
{
    public function create(ValidatedTeamData $data): Team;

    public function update(Team $team, ValidatedTeamData $data): Team;

    public function delete(Team $team): void;
}
