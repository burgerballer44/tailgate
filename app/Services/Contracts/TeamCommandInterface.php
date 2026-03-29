<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedTeamData;
use App\Models\Team;

interface TeamCommandInterface
{
    public function create(ValidatedTeamData $data): Team;

    public function update(Team $team, ValidatedTeamData $data): Team;

    public function delete(Team $team): void;
}
