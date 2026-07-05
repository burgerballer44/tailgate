<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Group;
use App\Models\Season;
use App\Models\Team;
use App\Services\Contracts\GameQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Centralizes game retrieval for scheduling and scoring flows.
 * Supports filtering by season, teams, and start-time status while keeping query construction consistent.
 */
class GameQueryService implements GameQueryInterface
{
    /**
     * Builds a game query from supported filter inputs used by listing endpoints.
     *
     * @param  array<string, mixed>  $filters  Associative query parameters used to filter games.
     * @return Builder The filtered game query.
     */
    public function query(array $filters): Builder
    {
        // Start from a base query and append only provided filters.
        $query = Game::query();

        if (isset($filters['season_id'])) {
            $query->where('season_id', $filters['season_id']);
        }

        if (! empty($filters['home_team_id'])) {
            $query->where('home_team_id', $filters['home_team_id']);
        }

        if (! empty($filters['away_team_id'])) {
            $query->where('away_team_id', $filters['away_team_id']);
        }

        if (array_key_exists('start_time_tbd', $filters) && $filters['start_time_tbd'] !== '' && $filters['start_time_tbd'] !== null) {
            // Normalize truthy/falsey input to a strict boolean for SQL comparison.
            $query->where('start_time_tbd', filter_var($filters['start_time_tbd'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    /**
     * Resolves eligible teams for a season so game forms only show valid matchups.
     *
     * @param  Season  $season  The season whose sport determines eligible teams.
     * @return array<int, string> Team organizations keyed by team ID for form select inputs.
     */
    public function getAvailableTeamsForSeason(Season $season): array
    {
        // Restrict teams to those that are registered for the season's sport.
        return Team::whereHas('sports', function ($query) use ($season) {
            $query->where('sport', $season->sport);
        })->get()->pluck('organization', 'id')->toArray();
    }

    /**
     * Get upcoming games available to a group based on followed teams and optional sport scope.
     *
     * @param  Group  $group  The group whose follows determine eligible games.
     * @return Collection<int, Game> Upcoming games sorted by start date-time.
     */
    public function getUpcomingGamesForGroup(Group $group): Collection
    {
        // Ensure follows are loaded once to avoid repeated lazy-loading queries.
        $group->loadMissing('follows');

        if ($group->follows->isEmpty()) {
            // No follows means no eligible games by design.
            return collect();
        }

        // Capture time boundaries once for consistent window checks.
        $now = now();
        $today = $now->toDateString();

        $query = Game::query()
            ->with(['season', 'homeTeam', 'awayTeam'])
            ->where(function (Builder $followQuery) use ($group) {
                // Build OR clauses per follow so any followed team can match.
                foreach ($group->follows as $follow) {
                    $followQuery->orWhere(function (Builder $eligibleGameQuery) use ($follow) {
                        $eligibleGameQuery
                            ->where(function (Builder $teamMatchQuery) use ($follow) {
                                $teamMatchQuery
                                    ->where('home_team_id', $follow->team_id)
                                    ->orWhere('away_team_id', $follow->team_id);
                            });

                        if ($follow->sport !== null) {
                            // Sport-scoped follows only match games in seasons of that sport.
                            $eligibleGameQuery->whereHas('season', function (Builder $seasonQuery) use ($follow) {
                                $seasonQuery->where('sport', $follow->sport->value);
                            });
                        }
                    });
                }
            })
            ->where(function (Builder $upcomingQuery) use ($now, $today) {
                // Upcoming includes exact-time future games and TBD games not before today.
                $upcomingQuery
                    ->where(function (Builder $scheduledQuery) use ($now) {
                        $scheduledQuery
                            ->where('start_time_tbd', false)
                            ->where('start_date_time', '>=', $now);
                    })
                    ->orWhere(function (Builder $tbdQuery) use ($today) {
                        $tbdQuery
                            ->where('start_time_tbd', true)
                            ->whereDate('start_date_time', '>=', $today);
                    });
            })
            ->orderBy('start_date_time');

        return $query->get();
    }

    /**
     * Build a game query for games available to a group's follow selection.
     *
     * @param  Group  $group  The group whose follow relationships define eligible games.
     * @return Builder The filtered game query.
     */
    public function getGamesForGroupFollowSelection(Group $group): Builder
    {
        // Restrict candidates to games whose seasons are followed by the group.
        return Game::query()
            ->whereHas('season.follows', function (Builder $query) use ($group) {
                $query->where('group_id', $group->id);
            })
            ->with(['homeTeam', 'awayTeam']);
    }

    /**
     * Get upcoming games for a group constrained to an inclusive end date-time window.
     *
     * @param  Group  $group  The group whose follows determine eligible games.
     * @param  \DateTimeInterface  $windowEnd  Inclusive upper bound for game start date-time.
     * @return Collection<int, Game> Upcoming games within the provided window.
     */
    public function getUpcomingGamesForGroupWithinWindow(Group $group, \DateTimeInterface $windowEnd): Collection
    {
        // Reuse the canonical upcoming-game query, then apply the end-window in memory.
        return $this->getUpcomingGamesForGroup($group)
            ->filter(function (Game $game) use ($windowEnd): bool {
                // Convert persisted datetime values to immutable objects for safe comparison.
                $gameDateTime = date_create_immutable((string) $game->start_date_time);

                if (! $gameDateTime instanceof \DateTimeImmutable) {
                    // Ignore malformed date values rather than throwing in read paths.
                    return false;
                }

                return $gameDateTime <= $windowEnd;
            })
            ->values();
    }
}
