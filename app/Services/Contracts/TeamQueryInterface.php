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
    /**
     * Build a team query from supported filters.
     *
     * @param array<string, mixed> $query Associative query parameters used to filter teams.
     * @return Builder The filtered team query.
     */
    public function query(array $query): Builder;

    /**
     * Get the teams that can be shown in follow-team selection workflows.
     *
     * @return Collection<int, \App\Models\Team> The team list ordered for stable display.
     */
    public function getAvailableTeamsForFollow(): Collection;
}
