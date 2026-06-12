<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Retrieves team information with flexible filtering and discovers teams available for following.
 * Provides team lookups filtered by sport and other criteria, and lists all teams for the
 * team-following interface, supporting team discovery and follow management.
 */
interface TeamQueryInterface
{
    public function query(array $query): Builder;

    public function getAvailableTeamsForFollow(): Collection;
}
