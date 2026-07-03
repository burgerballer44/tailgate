<?php

namespace App\Services;

use App\DTO\QuickPredictionPayload;
use App\Models\Game;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use App\Services\Contracts\GameQueryInterface;
use App\Services\Contracts\MemberQueryInterface;
use App\Services\Contracts\PredictionQueryInterface;
use App\Services\Contracts\QuickPredictionServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Builds the quick-prediction payload for the dashboard.
 */
class QuickPredictionService implements QuickPredictionServiceInterface
{
    private const PREDICTION_WINDOW_WEEKS = 2;

    /**
     * @param GameQueryInterface $gameQueryService Fetches upcoming group games within a time window.
     * @param MemberQueryInterface $memberQueryService Loads user memberships for quick predictions.
     * @param PredictionQueryInterface $predictionQueryService Loads predictions for player/game sets.
     */
    public function __construct(
        private GameQueryInterface $gameQueryService,
        private MemberQueryInterface $memberQueryService,
        private PredictionQueryInterface $predictionQueryService,
    ) {}

    /**
     * Return the dashboard copy for the configured prediction window.
     */
    public static function predictionWindowLabel(): string
    {
        return 'next '.self::PREDICTION_WINDOW_WEEKS.' weeks';
    }

    /**
     * Build the quick-prediction payload for the given user.
     */
    public function getQuickPredictionsPayloadForUser(User $user): QuickPredictionPayload
    {
        // Only approved members can submit predictions.
        // Pending members are visible on the dashboard but cannot interact with games,
        // so we exclude them entirely here.
        $approvedMemberships = $this->memberQueryService
            ->getApprovedMembershipsForUserWithQuickPredictionRelations($user);

        // The modal only shows games within a configurable rolling window.
        // This keeps the list focused and avoids surfacing games the user cannot realistically predict.
        $windowEnd = now()->addWeeks(self::PREDICTION_WINDOW_WEEKS);
        $dashboardOpenPredictionCount = 0;
        $gameEntries = collect();

        foreach ($approvedMemberships as $membership) {
            $group = $membership->group;

            // A membership should always have a group, but guard defensively in case
            // referential integrity is somehow violated.
            if (! $group) {
                continue;
            }

            $memberPlayers = $membership->players;

            // Ask the game query service for games that are upcoming (not in the past)
            // and within the 14-day window, filtered to teams the group follows.
            $upcomingGames = $this->gameQueryService
                ->getUpcomingGamesForGroupWithinWindow($group, $windowEnd);

            // Further group the games by followed team + sport so each modal card has
            // the correct team name and sport label.
            $teamSportGroups = $this->buildTeamSportGameGroups($group, $upcomingGames);

            // If this membership has no relevant games AND no players, there is nothing
            // to show in the modal for it — skip to avoid empty entries.
            if ($teamSportGroups->isEmpty() && $memberPlayers->isEmpty()) {
                continue;
            }

            // Build a keyed map of existing predictions
            // (game_id:player_id → prediction data)
            // so we can check in O(1) whether a slot is already filled.
            $predictionLookup = $this->buildPredictionLookup($memberPlayers, $upcomingGames);

            // Accumulate the count of missing predictions across all memberships.
            // This drives the badge number shown on the "Open quick predictions" button.
            $dashboardOpenPredictionCount += $this->countOpenPredictionSlots($memberPlayers, $upcomingGames, $predictionLookup);

            // Build one modal entry per game — each entry carries everything the
            // front-end needs to render a game card with player rows and action links.
            foreach ($teamSportGroups as $teamSportGroup) {
                foreach ($teamSportGroup['games'] as $game) {
                    $gameEntries->push($this->buildModalGameEntry($membership, $teamSportGroup, $game, $memberPlayers, $predictionLookup));
                }
            }
        }

        // Sort the game entries and wrap them in the final payload structure.
        return $this->transformToPayload(
            gameEntries: $gameEntries,
            openPredictionCount: $dashboardOpenPredictionCount,
            totalGroups: $approvedMemberships->count(),
        );
    }

    /**
     * Sort game entries and package them into a QuickPredictionPayload DTO.
     */
    private function transformToPayload(Collection $gameEntries, int $openPredictionCount, int $totalGroups): QuickPredictionPayload
    {
        // Sort all game entries into a stable chronological order, breaking ties by group
        // name. Using a single sortBy with a composite string key is simpler and performs
        // the same as chaining sortBy → thenBy on a small in-memory collection.
        $sortedGames = $gameEntries
            ->sortBy(fn (array $entry) => ($entry['game']['start_sort'] ?? '99999999999999').' '.($entry['group']['name'] ?? ''))
            ->values();

        return new QuickPredictionPayload(
            openPredictionCount: $openPredictionCount,
            totalGames: $sortedGames->count(),
            totalGroups: $totalGroups,
            games: $sortedGames->all(),
        );
    }

    /**
     * Build one UI-ready game entry for a membership/team-sport bucket.
     */
    private function buildModalGameEntry(Member $membership, array $teamSportGroup, Game $game, EloquentCollection $memberPlayers, array $predictionLookup): array
    {
        $group = $membership->group;
        $gameDateTime = date_create_immutable((string) $game->start_date_time);
        $seasonSport = Sport::tryFrom((string) $game->season?->sport);
        $sportLabel = $seasonSport?->value ?? (string) ($teamSportGroup['sport'] ?? 'Unknown sport');
        $sportIcon = $seasonSport?->htmlEntity()->character();

        // Build a human-readable label for the game start time.
        // TBD games show only the date with a "(TBD)" suffix because the exact
        // kick-off time is not yet known. Non-TBD games include the time.
        // If the date is unparseable (corrupt data), fall back to a safe string.
        $startLabel = $gameDateTime instanceof \DateTimeImmutable
            ? ($game->start_time_tbd
                ? $gameDateTime->format('M j, Y').' (TBD)'
                : $gameDateTime->format('M j, Y g:i A'))
            : 'Start time unavailable';

        // A sortable timestamp string (YmdHis) that lets the service sort cards
        // chronologically. Games without a parseable date are pushed to the end by
        // using the maximum sentinel value '99999999999999'.
        $startSort = $gameDateTime instanceof \DateTimeImmutable
            ? $gameDateTime->format('YmdHis')
            : '99999999999999';

        // Pick the first URL-valid logo for each team. The logos column is a JSON array
        // and may contain non-URL values or be empty, so we validate each entry.
        $homeLogo = collect($game->homeTeam->logos ?? [])->first(fn ($url) => filter_var($url, FILTER_VALIDATE_URL));
        $awayLogo = collect($game->awayTeam->logos ?? [])->first(fn ($url) => filter_var($url, FILTER_VALIDATE_URL));

        // Build one row per player. Each row shows the player's identity and their
        // current prediction for this game (null if they haven't submitted one yet).
        // The prediction key mirrors the key used in buildPredictionLookup so lookups
        // are always consistent.
        $playerRows = $memberPlayers->map(function ($player) use ($game, $predictionLookup): array {
            $predictionKey = $game->id.':'.$player->id;
            $prediction = $predictionLookup[$predictionKey] ?? null;

            return [
                'id' => $player->id,
                'ulid' => $player->ulid,
                'name' => $player->player_name,
                // null means no prediction submitted yet; a populated array means the
                // player already submitted and the form should show an update flow.
                'prediction' => $prediction,
            ];
        })->values();

        // Determine whether the prediction window is open for this game.
        // The status drives the card's visual state (open form vs. locked indicator).
        [$statusLabel, $statusReason, $isOpen] = $this->predictionStatusForGame($game);

        return [
            // A unique key for this card within the modal's Vue/Alpine component. Using
            // group ULID + game ID avoids collisions across memberships.
            'context_key' => $group->ulid.'|'.$game->id,

            // Group context — the front-end uses the member_ulid when building API calls
            // that are scoped to the membership rather than the group.
            'group' => [
                'ulid' => $group->ulid,
                'name' => $group->name,
                'member_ulid' => $membership->ulid,
            ],

            // Team and sport label for the card header.
            'team' => [
                'name' => $teamSportGroup['teamName'],
                'sport' => $sportLabel,
                'sport_icon' => $sportIcon,
            ],

            // Game details the card needs to render the matchup and control the form.
            'game' => [
                'id' => $game->id,
                'ulid' => $game->ulid,
                'home_team' => $game->homeTeam->display_name,
                'away_team' => $game->awayTeam->display_name,
                'home_logo' => $homeLogo,
                'away_logo' => $awayLogo,
                'start_label' => $startLabel,
                'sport_label' => $sportLabel,
                'sport_icon' => $sportIcon,
                'is_open' => $isOpen,
                'status_label' => $statusLabel,
                'status_reason' => $statusReason,
                // Used by transformToPayload() to sort cards chronologically without
                // re-parsing the human-readable label.
                'start_sort' => $startSort,
            ],

            // One row per player in the membership.
            'players' => $playerRows,

            // Route templates with placeholder tokens. The front-end replaces __PLAYER__
            // and __PREDICTION__ with real ULIDs when the user submits or updates a
            // prediction, keeping the back-end route structure out of the JS layer.
            'store_route_template' => route('groups.predictions.store', ['group' => $group, 'player' => '__PLAYER__']),
            'update_route_template' => route('groups.predictions.update', ['group' => $group, 'player' => '__PLAYER__', 'prediction' => '__PREDICTION__']),
            'group_upcoming_games_route' => route('groups.show', ['group' => $group, 'tab' => 'upcoming-games']),
        ];
    }

    /**
     * Return display status details for whether predictions are open for a game.
     */
    private function predictionStatusForGame(Game $game): array
    {
        $gameDateTime = date_create_immutable((string) $game->start_date_time);

        // Default to open if the date cannot be parsed — this is a safe fallback that
        // still lets the season-active check below close the window if needed.
        $isBeforeLock = true;

        if ($gameDateTime instanceof \DateTimeImmutable) {
            if ($game->start_time_tbd) {
                // TBD: compare only the date portion so predictions remain open until
                // the end of the game day regardless of the stored time component.
                $isBeforeLock = $gameDateTime->format('Y-m-d') >= now()->toDateString();
            } else {
                // Known time: lock the moment the game starts.
                $isBeforeLock = $gameDateTime >= new \DateTimeImmutable('now');
            }
        }

        $isSeasonActive = (bool) $game->season?->active;
        $isOpen = $isSeasonActive && $isBeforeLock;

        // Provide a reason so the UI can display a tooltip or sub-label explaining
        // the locked state to the user. Season inactive takes priority in messaging
        // because the lock time is irrelevant if the season is already over.
        if (! $isSeasonActive) {
            $statusReason = 'Season inactive';
        } elseif (! $isBeforeLock) {
            $statusReason = 'Prediction locked';
        } else {
            $statusReason = 'Open for prediction';
        }

        return [$isOpen ? 'Open' : 'Closed', $statusReason, $isOpen];
    }

    /**
     * Group upcoming games by followed team and sport for modal sections.
     */
    private function buildTeamSportGameGroups(\App\Models\Group $group, Collection $games): Collection
    {
        // Intermediate associative array keyed by "team_id:sport" so we can accumulate
        // games per bucket before converting to a Collection at the end.
        $grouped = [];

        foreach ($games as $game) {
            foreach ($group->follows as $follow) {
                // Only include the game if the followed team is one of the participants.
                $isFollowedTeamInGame = $game->home_team_id === $follow->team_id || $game->away_team_id === $follow->team_id;

                if (! $isFollowedTeamInGame) {
                    continue;
                }

                // If the follow is sport-scoped, skip games whose season sport does not
                // match. This is the core of the sport-filtering contract.
                if ($follow->sport !== null && $game->season?->sport !== $follow->sport->value) {
                    continue;
                }

                // Resolve display strings. The season sport is the canonical label;
                // the follow sport is a fallback for edge cases where the season is missing.
                $sportLabel = (string) ($game->season?->sport ?? $follow->sport?->value ?? 'Unknown sport');
                $teamName = (string) ($follow->team?->display_name ?? $follow->team?->organization ?? 'Unknown team');

                // The bucket key combines team and sport so that the same team followed
                // under different sports produces separate sections in the modal.
                $groupKey = $follow->team_id.':'.$sportLabel;

                if (! isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'key' => $groupKey,
                        'teamName' => $teamName,
                        'sport' => $sportLabel,
                        'games' => [],
                    ];
                }

                // Index by game ID to deduplicate when multiple follows match the same game.
                $grouped[$groupKey]['games'][$game->id] = $game;
            }
        }

        return collect($grouped)
            ->map(function (array $entry): array {
                return [
                    'key' => $entry['key'],
                    'teamName' => $entry['teamName'],
                    'sport' => $entry['sport'],
                    // Sort games within each bucket chronologically so they appear in
                    // the correct order when the modal iterates through them.
                    'games' => collect($entry['games'])
                        ->sortBy('start_date_time')
                        ->values(),
                ];
            })
            // Sort the sections themselves alphabetically by team name + sport so the
            // modal has a stable, predictable order regardless of insertion order.
            ->sortBy(fn (array $entry): string => strtolower($entry['teamName'].' '.$entry['sport']))
            ->values();
    }

    /**
     * Build an indexed prediction lookup keyed by game_id:player_id.
     */
    private function buildPredictionLookup(EloquentCollection $memberPlayers, Collection $upcomingGames): array
    {
        // Short-circuit: if there are no players or no games there can be no predictions,
        // so skip the database round-trip entirely.
        if ($memberPlayers->isEmpty() || $upcomingGames->isEmpty()) {
            return [];
        }

        $predictions = $this->predictionQueryService
            ->getPredictionsForPlayersAndGames($memberPlayers, $upcomingGames);

        $predictionLookup = [];

        foreach ($predictions as $prediction) {
            // Key format: "game_id:player_id" — this same format is used in
            // buildModalGameEntry and countOpenPredictionSlots, so they must all agree.
            $predictionLookup[$prediction->game_id.':'.$prediction->player_id] = [
                'id' => $prediction->id,
                'ulid' => $prediction->ulid,
                'home_team_prediction' => $prediction->home_team_prediction,
                'away_team_prediction' => $prediction->away_team_prediction,
            ];
        }

        return $predictionLookup;
    }

    /**
     * Count open and unfilled prediction slots across players and upcoming games.
     */
    private function countOpenPredictionSlots(EloquentCollection $memberPlayers, Collection $upcomingGames, array $predictionLookup): int
    {
        // Short-circuit: no players or no games means no open slots.
        if ($memberPlayers->isEmpty() || $upcomingGames->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($upcomingGames as $game) {
            // Skip games that are locked or belong to an inactive season — the user
            // cannot act on them so they should not inflate the badge count.
            if (! $this->isGameOpenForPrediction($game)) {
                continue;
            }

            foreach ($memberPlayers as $player) {
                // If the composite key is absent from the lookup, no prediction was
                // submitted for this player + game combination.
                if (! isset($predictionLookup[$game->id.':'.$player->id])) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Return whether predictions are currently open for the given game.
     */
    private function isGameOpenForPrediction(Game $game): bool
    {
        $gameDateTime = date_create_immutable((string) $game->start_date_time);
        $isBeforeLock = true;

        if ($gameDateTime instanceof \DateTimeImmutable) {
            if ($game->start_time_tbd) {
                $isBeforeLock = $gameDateTime->format('Y-m-d') >= now()->toDateString();
            } else {
                $isBeforeLock = $gameDateTime >= new \DateTimeImmutable('now');
            }
        }

        return (bool) $game->season?->active && $isBeforeLock;
    }
}
