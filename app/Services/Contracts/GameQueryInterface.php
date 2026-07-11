<?php

namespace App\Services\Contracts;

use App\Models\Game;
use App\Models\Group;
use App\Models\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Defines read operations for game data.
 *
 * Implementations provide filtered game queries, season-aware team selection,
 * and group-scoped upcoming-game retrieval.
 */
interface GameQueryInterface
{
    /**
     * Build a game query from supported filters.
     *
     * @param  array<string, mixed>  $filters  Associative query parameters used to filter games.
     * @return Builder The filtered game query.
     */
    public function query(array $filters): Builder;

    /**
     * Resolve teams eligible for a season's game forms.
     *
     * @param  Season  $season  The season whose sport determines eligible teams.
     * @return array<int, string> Team organizations keyed by team ID for form selection.
     */
    public function getAvailableTeamsForSeason(Season $season): array;

    /**
     * Get upcoming games that are eligible for a group's follows.
     *
        * A game is eligible when either team matches a followed team and the group
        * follows the season when explicit season follows are configured.
     *
     * @param  Group  $group  The group whose follow configuration defines eligible games.
     * @return Collection<int, Game> Upcoming games sorted by start date-time.
     */
    public function getUpcomingGamesForGroup(Group $group): Collection;

    /**
     * Build a game query for games available to a group's follow selection.
     *
     * @param  Group  $group  The group whose follow relationships define eligible games.
     * @return Builder The filtered game query.
     */
    public function getGamesForGroupFollowSelection(Group $group): Builder;

    /**
     * Get upcoming games for a group constrained to a configured window end time.
     *
     * @param  Group  $group  The group whose follows define eligible games.
     * @param  \DateTimeInterface  $windowEnd  Inclusive upper bound for game start date-time.
     * @return Collection<int, Game> Upcoming games within the provided window.
     */
    public function getUpcomingGamesForGroupWithinWindow(Group $group, \DateTimeInterface $windowEnd): Collection;
}
