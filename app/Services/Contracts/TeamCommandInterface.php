<?php

namespace App\Services\Contracts;

use App\Models\Team;
use App\DTO\ValidatedTeamData;

interface TeamCommandInterface
{
    public function create(ValidatedTeamData $data): Team;
    public function update(Team $team, ValidatedTeamData $data): Team;
    public function delete(Team $team): void;
}