<?php

namespace App\Services;

use App\Models\Team;
use App\Services\Contracts\TeamQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Supports team discovery and selection workflows.
 * Provides filtered team retrieval with predictable ordering and relationship loading.
 */
class TeamQueryService implements TeamQueryInterface
{
    /**
     * Builds a team query from supported filters for discovery and admin listings.
     *
     * @param  array<string, mixed>  $query  Associative query parameters used by Team::filter().
     * @return Builder The filtered team query with sports eager loaded for display.
     */
    public function query(array $query): Builder
    {
        // Apply model filters and eager-load sports used by most consumers.
        return Team::filter($query)->with('sports');
    }

    /**
     * Lists teams in deterministic order for follow operations.
     *
     * @return Collection<int, Team> The complete team collection ordered for stable UI rendering.
     */
    public function getAvailableTeamsForFollow(): Collection
    {
        // Deterministic ordering keeps results stable across calls.
        return Team::query()
            ->orderBy('organization')
            ->orderBy('designation')
            ->orderBy('abbreviation')
            ->get();
    }
}
