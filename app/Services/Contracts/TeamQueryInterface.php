<?php

namespace App\Services\Contracts;

use App\Models\Team;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Defines read operations for team records.
 *
 * Implementations provide filtered team queries and lookups used by follow
 * configuration.
 */
interface TeamQueryInterface
{
    /**
     * Build a team query from supported filters.
     *
     * @param  array<string, mixed>  $query  Associative query parameters used to filter teams.
     * @return Builder The filtered team query.
     */
    public function query(array $query): Builder;

    /**
     * Get teams that are eligible for follow configuration.
     *
     * @return Collection<int, Team> The team list ordered for stable selection.
     */
    public function getAvailableTeamsForFollow(): Collection;
}
