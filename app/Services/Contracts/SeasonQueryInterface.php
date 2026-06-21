<?php

namespace App\Services\Contracts;

use App\Models\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Retrieves season information and related game data, and discovers available seasons.
 * Provides season lookups with eager-loaded games and teams, and lists active seasons for following workflows,
 * supporting season browsing and game management features.
 */
interface SeasonQueryInterface
{
    /**
     * Build a season query from supported filters.
     *
     * @param array<string, mixed> $query Associative query parameters used to filter seasons.
     * @return Builder The filtered season query.
     */
    public function query(array $query): Builder;

    /**
     * Load a season with its games and related teams.
     *
     * @param Season $season The season to load.
     * @return Season The same season instance with nested relationships loaded.
     */
    public function loadWithGames(Season $season): Season;

    /**
     * Get active seasons available for follow workflows.
     *
     * @return Collection<int, \App\Models\Season> Active seasons ordered for selection UIs.
     */
    public function getAvailableSeasonsForFollow(): Collection;
}
