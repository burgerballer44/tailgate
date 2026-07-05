<?php

namespace App\Services\Contracts;

use App\Models\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Defines read operations for seasons and season-related lookups.
 */
interface SeasonQueryInterface
{
    /**
     * Build a season query from supported filters.
     *
     * @param  array<string, mixed>  $query  Associative query parameters used to filter seasons.
     * @return Builder The filtered season query.
     */
    public function query(array $query): Builder;

    /**
     * Load a season with its games and related teams.
     *
     * @param  Season  $season  The season to load.
     * @return Season The same season instance with nested relationships loaded.
     */
    public function loadWithGames(Season $season): Season;

    /**
     * Get active seasons that can be used when configuring follows.
     *
     * @return Collection<int, Season> Active seasons ordered for stable selection.
     */
    public function getAvailableSeasonsForFollow(): Collection;
}
