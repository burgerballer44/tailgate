<?php

namespace App\Services;

use App\Models\Team;
use App\Services\Contracts\TeamQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Supports team discovery and selection workflows used across follow and scheduling features.
 * Provides filtered team retrieval with predictable ordering and relationship loading.
 */
class TeamQueryService implements TeamQueryInterface
{
    /**
     * Builds a team query from supported filters for discovery and admin listings.
     *
     * @param  array  $query  An associative array of query parameters to filter teams.
     * @return Builder  A query builder instance for the filtered teams.
     */
    public function query(array $query): Builder
    {
        return Team::filter($query)->with('sports');
    }

    /**
     * Lists teams in deterministic order for follow-team selection workflows.
     */
    public function getAvailableTeamsForFollow(): Collection
    {
        return Team::query()
            ->orderBy('organization')
            ->orderBy('designation')
            ->orderBy('conference')
            ->orderBy('abbreviation')
            ->get();
    }
}
