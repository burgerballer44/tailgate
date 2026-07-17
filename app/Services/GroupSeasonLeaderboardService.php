<?php

namespace App\Services;

use App\DTO\GamePointsContext;
use App\DTO\GameRawPredictionData;
use App\DTO\GameRawPredictionPlayerRowData;
use App\DTO\MissingPredictionContext;
use App\DTO\PlayerLeaderboardRowData;
use App\DTO\PlayerGamePointsResult;
use App\DTO\PlayerSeasonTotal;
use App\DTO\SeasonResultsViewData;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupSeasonFollow;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Prediction;
use App\ScoringPolicies\Contracts\GroupPointsPolicyInterface;
use App\ScoringPolicies\PlacementPointsPolicy;
use App\ScoringPolicies\PredictionDifferenceFromScorePointsPolicy;
use App\Services\Contracts\GroupSeasonLeaderboardServiceInterface;
use App\Services\Contracts\PredictionScoringPolicyCatalogInterface;
use DateTimeInterface;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Builds season-scoped results payloads.
 *
 * Task 5 introduces optimized query orchestration and eligibility filtering.
 * Point aggregation and ranking remain in later tasks.
 */
class GroupSeasonLeaderboardService implements GroupSeasonLeaderboardServiceInterface
{
    public function __construct(
        private readonly PredictionScoringPolicyCatalogInterface $policyCatalog,
    ) {}

    /**
     * Build leaderboard and raw game scoring data for a group-season context.
     */
    public function buildSeasonResults(int $groupId, int $seasonId, ?int $asOfGameId = null): SeasonResultsViewData
    {
        $group = Group::query()->with(['follows', 'seasonFollows'])->findOrFail($groupId);

        $seasonFollow = GroupSeasonFollow::query()
            ->where('group_id', $groupId)
            ->where('season_id', $seasonId)
            ->first();

        $pointsPolicy = $seasonFollow?->prediction_scoring_policy ?: $this->policyCatalog->defaultKey();

        [$seasonGames, $scorableGames, $excludedGames] = $this->loadSeasonGames(
            group: $group,
            seasonId: $seasonId,
            asOfGameId: $asOfGameId,
        );

        $membersWithSeasonHistory = Member::query()
            ->where('group_id', $groupId)
            ->whereIn('status', [
                MemberStatus::APPROVED->value,
                MemberStatus::LEFT->value,
                MemberStatus::REMOVED->value,
            ])
            ->with('players')
            ->get();

        $eligiblePlayersByGame = $this->resolveEligiblePlayersByGame($membersWithSeasonHistory, $seasonGames);
        $predictionCount = $seasonGames->sum(fn (Game $game): int => $game->predictions->count());
        $playerNamesById = $membersWithSeasonHistory
            ->flatMap(fn (Member $member) => $member->players->mapWithKeys(fn ($player): array => [$player->id => $player->player_name]))
            ->all();

        $leaderboardRows = $this->buildLeaderboardRows(
            games: $scorableGames,
            followedTeamIds: $group->follows->pluck('team_id')->all(),
            eligiblePlayersByGame: $eligiblePlayersByGame,
            playerNamesById: $playerNamesById,
            pointsPolicyKey: $pointsPolicy,
        );

        $rawGameRows = $this->buildRawGameRows(
            games: $seasonGames,
            followedTeamIds: $group->follows->pluck('team_id')->all(),
            eligiblePlayersByGame: $eligiblePlayersByGame,
            playerNamesById: $playerNamesById,
            pointsPolicyKey: $pointsPolicy,
        );

        return new SeasonResultsViewData(
            groupId: $groupId,
            seasonId: $seasonId,
            pointsPolicy: $pointsPolicy,
            generatedAt: new DateTimeImmutable(),
            leaderboardRows: $leaderboardRows,
            rawGameRows: $rawGameRows,
            meta: [
                'as_of_game_id' => $asOfGameId,
                'status' => 'query-orchestrated',
                'game_ids' => $scorableGames->pluck('id')->values()->all(),
                'season_game_ids' => $seasonGames->pluck('id')->values()->all(),
                'total_games_loaded' => $scorableGames->count(),
                'total_season_games_loaded' => $seasonGames->count(),
                'total_predictions_loaded' => $predictionCount,
                'total_leaderboard_rows' => count($leaderboardRows),
                'total_raw_game_rows' => count($rawGameRows),
                'eligible_player_ids_by_game' => $eligiblePlayersByGame,
                'excluded_games' => $excludedGames,
                'notes' => [
                    'Query orchestration, leaderboard aggregation, and raw row assembly are active.',
                ],
            ],
        );
    }

    /**
     * Assemble raw per-game prediction rows for the selected season snapshot.
     *
     * @param  Collection<int, Game>  $games
     * @param  array<int, int>  $followedTeamIds
     * @param  array<string, array<int, int>>  $eligiblePlayersByGame
     * @param  array<int, string>  $playerNamesById
     * @return array<int, GameRawPredictionData>
     */
    private function buildRawGameRows(
        Collection $games,
        array $followedTeamIds,
        array $eligiblePlayersByGame,
        array $playerNamesById,
        string $pointsPolicyKey,
    ): array {
        $policy = $this->resolvePointsPolicy($pointsPolicyKey);
        $rows = [];
        $weekLabelsByGameId = $this->buildWeekLabelsByGameId($games);

        foreach ($games as $game) {
            $eligiblePlayerIds = $eligiblePlayersByGame[(string) $game->id] ?? [];

            $followedTeamId = $this->resolveFollowedTeamIdForGame($game, $followedTeamIds);
            $gamePointsByPlayer = $this->isScorableGame($game) && $eligiblePlayerIds !== []
                ? $this->calculateGamePointResultsByPlayer(
                    game: $game,
                    eligiblePlayerIds: $eligiblePlayerIds,
                    followedTeamId: $followedTeamId,
                    policy: $policy,
                )
                : [];

            [$actualFollowedScore, $actualOpponentScore] = $this->actualScoresForFollowedTeamOrNull($game, $followedTeamId);
            $followedTeamName = $game->home_team_id === $followedTeamId
                ? ((string) ($game->homeTeam?->display_name ?: $game->homeTeam?->organization ?: 'Unknown team'))
                : ((string) ($game->awayTeam?->display_name ?: $game->awayTeam?->organization ?: 'Unknown team'));
            $opponentTeamName = $game->home_team_id === $followedTeamId
                ? ((string) ($game->awayTeam?->display_name ?: $game->awayTeam?->organization ?: 'Unknown team'))
                : ((string) ($game->homeTeam?->display_name ?: $game->homeTeam?->organization ?: 'Unknown team'));

            $playerRows = [];

            foreach ($eligiblePlayerIds as $playerId) {
                /** @var Prediction|null $prediction */
                $prediction = $game->predictions->firstWhere('player_id', $playerId);
                [$predictedFollowed, $predictedOpponent] = $prediction instanceof Prediction
                    ? $this->predictedScoresForFollowedTeam($game, $prediction, $followedTeamId)
                    : [null, null];

                $result = $gamePointsByPlayer[$playerId] ?? new PlayerGamePointsResult(
                    playerId: $playerId,
                    gameId: $game->id,
                    points: 0.0,
                    penaltyPoints: 0,
                    calculationNotes: ['result-unavailable'],
                );

                $playerRows[] = new GameRawPredictionPlayerRowData(
                    playerId: $playerId,
                    playerName: $playerNamesById[$playerId] ?? 'Unknown player',
                    predictedFollowedScore: $predictedFollowed,
                    predictedOpponentScore: $predictedOpponent,
                    penaltyPoints: $result->penaltyPoints,
                    gamePoints: $result->points,
                    calculationNotes: $result->calculationNotes,
                );
            }

            $rows[] = new GameRawPredictionData(
                gameId: $game->id,
                weekLabel: $weekLabelsByGameId[$game->id] ?? 'Week ?',
                gameStatus: $this->resolveGameStatus($game),
                followedTeam: $followedTeamName,
                opponentTeam: $opponentTeamName,
                actualFollowedScore: $actualFollowedScore,
                actualOpponentScore: $actualOpponentScore,
                playerRows: $playerRows,
            );
        }

        return $rows;
    }

    /**
     * Aggregate season totals and ranking metadata into leaderboard rows.
     *
     * @param  Collection<int, Game>  $games
     * @param  array<int, int>  $followedTeamIds
     * @param  array<string, array<int, int>>  $eligiblePlayersByGame
     * @param  array<int, string>  $playerNamesById
     * @return array<int, PlayerLeaderboardRowData>
     */
    private function buildLeaderboardRows(
        Collection $games,
        array $followedTeamIds,
        array $eligiblePlayersByGame,
        array $playerNamesById,
        string $pointsPolicyKey,
    ): array {
        $policy = $this->resolvePointsPolicy($pointsPolicyKey);

        /** @var array<int, float> $totals */
        $totals = [];
        /** @var array<int, int> $previousRanks */
        $previousRanks = [];
        /** @var array<int, int> $currentRanks */
        $currentRanks = [];

        foreach ($games as $game) {
            $gameKey = (string) $game->id;
            $eligiblePlayerIds = $eligiblePlayersByGame[$gameKey] ?? [];

            if ($eligiblePlayerIds === []) {
                continue;
            }

            $followedTeamId = $this->resolveFollowedTeamIdForGame($game, $followedTeamIds);
            $gamePointsByPlayer = $this->calculateGamePointsByPlayer(
                game: $game,
                eligiblePlayerIds: $eligiblePlayerIds,
                followedTeamId: $followedTeamId,
                policy: $policy,
            );

            foreach ($gamePointsByPlayer as $playerId => $gamePoints) {
                $totals[$playerId] = ($totals[$playerId] ?? 0.0) + $gamePoints;
            }

            $currentRanks = $this->computeRankMap(
                totals: $totals,
                playerNamesById: $playerNamesById,
                priorRanks: $currentRanks,
                policy: $policy,
            );

            // Track the immediate prior snapshot to support previous-rank rendering.
            $previousRanks = $currentRanks;
        }

        if ($totals === []) {
            return [];
        }

        // Reconstruct previous snapshot by replaying all games before the current week bucket.
        $priorSnapshotRanks = $this->reconstructPreviousSnapshotRanks(
            games: $games,
            followedTeamIds: $followedTeamIds,
            eligiblePlayersByGame: $eligiblePlayersByGame,
            playerNamesById: $playerNamesById,
            policy: $policy,
        );

        $finalRanks = $this->computeRankMap(
            totals: $totals,
            playerNamesById: $playerNamesById,
            priorRanks: $priorSnapshotRanks,
            policy: $policy,
        );

        $leaderTotal = min($totals);
        $rows = [];

        foreach ($finalRanks as $playerId => $rank) {
            $previousRank = $priorSnapshotRanks[$playerId] ?? null;
            $rows[] = new PlayerLeaderboardRowData(
                playerId: $playerId,
                playerName: $playerNamesById[$playerId] ?? 'Unknown player',
                totalPoints: (float) $totals[$playerId],
                rank: $rank,
                previousRank: $previousRank,
                rankChange: $previousRank !== null ? $previousRank - $rank : 0,
                pointsBehindLeader: (float) ($totals[$playerId] - $leaderTotal),
            );
        }

        usort($rows, fn (PlayerLeaderboardRowData $left, PlayerLeaderboardRowData $right): int => $left->rank <=> $right->rank);

        return $rows;
    }

    /**
     * Compute per-game points keyed by player id for eligible players.
     *
     * @param  array<int, int>  $eligiblePlayerIds
     * @return array<int, float>
     */
    private function calculateGamePointsByPlayer(
        Game $game,
        array $eligiblePlayerIds,
        int $followedTeamId,
        GroupPointsPolicyInterface $policy,
    ): array {
        return collect($this->calculateGamePointResultsByPlayer($game, $eligiblePlayerIds, $followedTeamId, $policy))
            ->map(fn (PlayerGamePointsResult $result): float => $result->points)
            ->all();
    }

    /**
     * Compute per-game scoring results keyed by player id for eligible players.
     *
     * @param  array<int, int>  $eligiblePlayerIds
     * @return array<int, PlayerGamePointsResult>
     */
    private function calculateGamePointResultsByPlayer(
        Game $game,
        array $eligiblePlayerIds,
        int $followedTeamId,
        GroupPointsPolicyInterface $policy,
    ): array {
        $predictionsByPlayer = $game->predictions
            ->filter(fn (Prediction $prediction): bool => in_array($prediction->player_id, $eligiblePlayerIds, true))
            ->keyBy('player_id');

        $submittedPreScores = [];

        foreach ($predictionsByPlayer as $playerId => $prediction) {
            $submittedPreScores[(int) $playerId] = $this->calculatePredictionDifference($game, $prediction, $followedTeamId);
        }

        $placementRanks = $this->computePlacementRanks($submittedPreScores);
        $worstSubmittedDifference = $submittedPreScores !== [] ? max($submittedPreScores) : null;
        $worstSubmittedPlacement = $placementRanks !== [] ? max($placementRanks) : null;

        $resultsByPlayer = [];

        foreach ($eligiblePlayerIds as $playerId) {
            /** @var Prediction|null $prediction */
            $prediction = $predictionsByPlayer->get($playerId);

            if ($prediction instanceof Prediction) {
                $context = $this->buildGamePointsContext(
                    game: $game,
                    prediction: $prediction,
                    followedTeamId: $followedTeamId,
                    placementRank: $placementRanks[$playerId] ?? null,
                );

                $resultsByPlayer[$playerId] = $policy->calculateGamePoints($context);

                continue;
            }

            $missingContext = new MissingPredictionContext(
                gameId: $game->id,
                playerId: $playerId,
                worstSubmittedGamePoints: $policy::key() === PlacementPointsPolicy::key()
                    ? ($worstSubmittedPlacement !== null ? (float) $worstSubmittedPlacement : null)
                    : ($worstSubmittedDifference !== null ? (float) $worstSubmittedDifference : null),
                fallbackPoints: (float) config('prediction_results.missing_prediction.'.PredictionDifferenceFromScorePointsPolicy::key().'.no_submissions_fallback_points', 14),
            );

            $resultsByPlayer[$playerId] = $policy->assignMissingPredictionPoints($missingContext);
        }

        return $resultsByPlayer;
    }

    /**
     * Build game-points context for a submitted prediction.
     */
    private function buildGamePointsContext(
        Game $game,
        Prediction $prediction,
        int $followedTeamId,
        ?int $placementRank,
    ): GamePointsContext {
        [$actualFollowed, $actualOpponent] = $this->actualScoresForFollowedTeam($game, $followedTeamId);
        [$predictedFollowed, $predictedOpponent] = $this->predictedScoresForFollowedTeam($game, $prediction, $followedTeamId);

        return new GamePointsContext(
            gameId: $game->id,
            playerId: $prediction->player_id,
            actualFollowedScore: $actualFollowed,
            actualOpponentScore: $actualOpponent,
            predictedFollowedScore: $predictedFollowed,
            predictedOpponentScore: $predictedOpponent,
            penaltyPoints: 0,
            placementRank: $placementRank,
        );
    }

    /**
     * Resolve followed-team-relative actual scores.
     *
     * @return array{0: int, 1: int}
     */
    private function actualScoresForFollowedTeam(Game $game, int $followedTeamId): array
    {
        if ($game->home_team_id === $followedTeamId) {
            return [(int) $game->home_team_score, (int) $game->away_team_score];
        }

        return [(int) $game->away_team_score, (int) $game->home_team_score];
    }

    /**
     * Resolve followed-team-relative predicted scores.
     *
     * @return array{0: int, 1: int}
     */
    private function predictedScoresForFollowedTeam(Game $game, Prediction $prediction, int $followedTeamId): array
    {
        if ($game->home_team_id === $followedTeamId) {
            return [(int) $prediction->home_team_prediction, (int) $prediction->away_team_prediction];
        }

        return [(int) $prediction->away_team_prediction, (int) $prediction->home_team_prediction];
    }

    /**
     * Calculate absolute prediction difference for placement pre-ranking.
     */
    private function calculatePredictionDifference(Game $game, Prediction $prediction, int $followedTeamId): int
    {
        [$actualFollowed, $actualOpponent] = $this->actualScoresForFollowedTeam($game, $followedTeamId);
        [$predictedFollowed, $predictedOpponent] = $this->predictedScoresForFollowedTeam($game, $prediction, $followedTeamId);

        return abs($predictedFollowed - $actualFollowed) + abs($predictedOpponent - $actualOpponent);
    }

    /**
     * Compute deterministic placement ranks for submitted prediction differences.
     *
     * @param  array<int, int>  $submittedPreScores
     * @return array<int, int>
     */
    private function computePlacementRanks(array $submittedPreScores): array
    {
        asort($submittedPreScores, SORT_NUMERIC);

        $ranks = [];
        $rank = 1;

        foreach (array_keys($submittedPreScores) as $playerId) {
            $ranks[(int) $playerId] = $rank;
            $rank++;
        }

        return $ranks;
    }

    /**
     * Compute ranking map from current totals and prior ranks using policy tie-break behavior.
     *
     * @param  array<int, float>  $totals
     * @param  array<int, string>  $playerNamesById
     * @param  array<int, int>  $priorRanks
     * @return array<int, int>
     */
    private function computeRankMap(
        array $totals,
        array $playerNamesById,
        array $priorRanks,
        GroupPointsPolicyInterface $policy,
    ): array {
        $seasonTotals = [];

        foreach ($totals as $playerId => $totalPoints) {
            $seasonTotals[] = new PlayerSeasonTotal(
                playerId: $playerId,
                playerName: $playerNamesById[$playerId] ?? 'Unknown player',
                totalPoints: (float) $totalPoints,
                previousRank: $priorRanks[$playerId] ?? null,
            );
        }

        usort($seasonTotals, fn (PlayerSeasonTotal $left, PlayerSeasonTotal $right): int => $policy->compareForRanking($left, $right));

        $rankMap = [];
        $rank = 1;

        foreach ($seasonTotals as $seasonTotal) {
            $rankMap[$seasonTotal->playerId] = $rank;
            $rank++;
        }

        return $rankMap;
    }

    /**
     * Reconstruct rank map from all but the last processed game snapshot.
     *
     * @param  Collection<int, Game>  $games
     * @param  array<int, int>  $followedTeamIds
     * @param  array<string, array<int, int>>  $eligiblePlayersByGame
     * @param  array<int, string>  $playerNamesById
     * @return array<int, int>
     */
    private function reconstructPreviousSnapshotRanks(
        Collection $games,
        array $followedTeamIds,
        array $eligiblePlayersByGame,
        array $playerNamesById,
        GroupPointsPolicyInterface $policy,
    ): array {
        if ($games->count() <= 1) {
            return [];
        }

        $currentWeekKey = $this->weekBucketKey($games->last());
        $priorGames = $games
            ->filter(fn (Game $game): bool => $this->weekBucketKey($game) !== $currentWeekKey)
            ->values();

        if ($priorGames->isEmpty()) {
            return [];
        }

        $totals = [];
        $currentRanks = [];

        foreach ($priorGames as $game) {
            $eligiblePlayerIds = $eligiblePlayersByGame[(string) $game->id] ?? [];

            if ($eligiblePlayerIds === []) {
                continue;
            }

            $followedTeamId = $this->resolveFollowedTeamIdForGame($game, $followedTeamIds);
            $gamePointsByPlayer = $this->calculateGamePointsByPlayer($game, $eligiblePlayerIds, $followedTeamId, $policy);

            foreach ($gamePointsByPlayer as $playerId => $points) {
                $totals[$playerId] = ($totals[$playerId] ?? 0.0) + $points;
            }

            $currentRanks = $this->computeRankMap($totals, $playerNamesById, $currentRanks, $policy);
        }

        return $currentRanks;
    }

    /**
     * Resolve a followed team id for the game with deterministic home-team precedence.
     *
     * @param  array<int, int>  $followedTeamIds
     */
    private function resolveFollowedTeamIdForGame(Game $game, array $followedTeamIds): int
    {
        if (in_array($game->home_team_id, $followedTeamIds, true)) {
            return (int) $game->home_team_id;
        }

        return (int) $game->away_team_id;
    }

    /**
     * Resolve configured policy key to concrete policy strategy.
     */
    private function resolvePointsPolicy(string $pointsPolicyKey): GroupPointsPolicyInterface
    {
        return match ($pointsPolicyKey) {
            PlacementPointsPolicy::key() => new PlacementPointsPolicy(),
            default => new PredictionDifferenceFromScorePointsPolicy(),
        };
    }

    /**
     * Load season games for a group and separate scorable games from excluded ones.
     *
     * @return array{0: Collection<int, Game>, 1: Collection<int, Game>, 2: array<int, array<string, mixed>>}
     */
    private function loadSeasonGames(Group $group, int $seasonId, ?int $asOfGameId): array
    {
        if ($group->follows->isEmpty()) {
            return [collect(), collect(), []];
        }

        $followedTeamIds = $group->follows->pluck('team_id')->all();

        $games = Game::query()
            ->where('season_id', $seasonId)
            ->where(function ($query) use ($followedTeamIds): void {
                $query->whereIn('home_team_id', $followedTeamIds)
                    ->orWhereIn('away_team_id', $followedTeamIds);
            })
            ->when($asOfGameId !== null, fn ($query) => $query->where('id', '<=', $asOfGameId))
            ->with([
                'season',
                'homeTeam',
                'awayTeam',
                'predictions.player.member',
            ])
            ->orderBy('start_date_time')
            ->orderBy('id')
            ->get();

        $excludedGames = [];

        $scorableGames = $games->filter(function (Game $game) use (&$excludedGames): bool {
            $isScorable = $this->isScorableGame($game);

            if (! $isScorable) {
                $excludedGames[] = [
                    'game_id' => $game->id,
                    'reason' => 'non_scorable_status_or_missing_score',
                ];
            }

            return $isScorable;
        })->values();

        return [$games, $scorableGames, $excludedGames];
    }

    /**
     * Determine if a game is scorable based on persisted final score values.
     */
    private function isScorableGame(Game $game): bool
    {
        $homeRaw = $game->getRawOriginal('home_team_score');
        $awayRaw = $game->getRawOriginal('away_team_score');

        return is_numeric($homeRaw) && is_numeric($awayRaw);
    }

    /**
     * Resolve a display status for a game row from the stored score payloads.
     */
    private function resolveGameStatus(Game $game): string
    {
        if ($this->isScorableGame($game)) {
            return 'completed';
        }

        $rawHome = strtoupper(trim((string) $game->getRawOriginal('home_team_score')));
        $rawAway = strtoupper(trim((string) $game->getRawOriginal('away_team_score')));
        $rawStatus = $rawHome !== '' ? $rawHome : $rawAway;

        return match (true) {
            in_array($rawStatus, ['PST', 'PPD', 'POSTPONED'], true) => 'postponed',
            in_array($rawStatus, ['CANC', 'CANCELLED', 'CANCELED'], true) => 'canceled',
            $this->toDateTimeImmutable($game->start_date_time) instanceof DateTimeImmutable
                && $this->toDateTimeImmutable($game->start_date_time) > new DateTimeImmutable() => 'scheduled',
            default => 'pending',
        };
    }

    /**
     * Build stable week labels from calendar-week buckets, with sequence fallback.
     *
     * @param  Collection<int, Game>  $games
     * @return array<int, string>
     */
    private function buildWeekLabelsByGameId(Collection $games): array
    {
        $labels = [];
        $sequenceByBucket = [];
        $nextSequence = 1;

        foreach ($games as $game) {
            $bucket = $this->weekBucketKey($game);

            if (! array_key_exists($bucket, $sequenceByBucket)) {
                $sequenceByBucket[$bucket] = $nextSequence;
                $nextSequence++;
            }

            $labels[$game->id] = 'Week '.$sequenceByBucket[$bucket];
        }

        return $labels;
    }

    /**
     * Resolve the calendar week bucket used for labels and prior-week ranking snapshots.
     */
    private function weekBucketKey(Game $game): string
    {
        $gameTime = $this->toDateTimeImmutable($game->start_date_time);

        if ($gameTime instanceof DateTimeImmutable) {
            return $gameTime->format('o-W');
        }

        return 'undated-'.$game->id;
    }

    /**
     * Resolve membership-window-eligible players for each game.
     *
     * The caller provides membership records that are allowed to participate in
     * historical calculations (for example approved + formerly removed members).
     *
     * @param  Collection<int, Member>  $members
     * @param  Collection<int, Game>  $games
     * @return array<string, array<int, int>>
     */
    private function resolveEligiblePlayersByGame(Collection $members, Collection $games): array
    {
        $eligiblePlayersByGame = [];

        foreach ($games as $game) {
            $eligiblePlayerIds = [];

            foreach ($members as $member) {
                if (! $this->isMemberEligibleForGame($member, $game)) {
                    continue;
                }

                foreach ($member->players as $player) {
                    $eligiblePlayerIds[] = $player->id;
                }
            }

            $eligiblePlayersByGame[(string) $game->id] = array_values(array_unique($eligiblePlayerIds));
        }

        return $eligiblePlayersByGame;
    }

    /**
     * Evaluate a member's eligibility window for a game using configured join/leave semantics.
     */
    private function isMemberEligibleForGame(Member $member, Game $game): bool
    {
        $gameTime = $this->toDateTimeImmutable($game->start_date_time);

        // If game start is missing/unparseable, avoid excluding by window until data quality is corrected.
        if (! $gameTime instanceof DateTimeImmutable) {
            return true;
        }

        $joinedAt = $this->resolveMemberJoinedAt($member);
        $leftAt = $this->resolveMemberLeftAt($member);

        $joinedInclusive = (bool) config('prediction_results.membership.joined_at_inclusive', true);
        $leftExclusive = (bool) config('prediction_results.membership.left_at_exclusive', true);

        if ($joinedAt instanceof DateTimeImmutable) {
            $joinedComparison = $joinedInclusive
                ? ($gameTime < $joinedAt)
                : ($gameTime <= $joinedAt);

            if ($joinedComparison) {
                return false;
            }
        }

        if ($leftAt instanceof DateTimeImmutable) {
            $leftComparison = $leftExclusive
                ? ($gameTime >= $leftAt)
                : ($gameTime > $leftAt);

            if ($leftComparison) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve followed-team-relative actual scores when the game is scorable.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function actualScoresForFollowedTeamOrNull(Game $game, int $followedTeamId): array
    {
        if (! $this->isScorableGame($game)) {
            return [null, null];
        }

        return $this->actualScoresForFollowedTeam($game, $followedTeamId);
    }

    /**
     * Resolve member join timestamp, using created_at when explicit join fields are unavailable.
     */
    private function resolveMemberJoinedAt(Member $member): ?DateTimeImmutable
    {
        if (Schema::hasColumn($member->getTable(), 'joined_at')) {
            return $this->toDateTimeImmutable($member->joined_at);
        }

        return $this->toDateTimeImmutable($member->created_at);
    }

    /**
     * Resolve member leave timestamp when available.
     */
    private function resolveMemberLeftAt(Member $member): ?DateTimeImmutable
    {
        if (Schema::hasColumn($member->getTable(), 'left_at')) {
            $leftAt = $this->toDateTimeImmutable($member->left_at);

            if ($leftAt instanceof DateTimeImmutable) {
                return $leftAt;
            }
        }

        // Legacy fallback for environments that have not yet added left_at.
        if (in_array($member->status, [MemberStatus::LEFT->value, MemberStatus::REMOVED->value], true)) {
            return $this->toDateTimeImmutable($member->updated_at);
        }

        return null;
    }

    /**
     * Safely convert mixed datetime values to immutable instances.
     */
    private function toDateTimeImmutable(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (is_string($value) && trim($value) !== '') {
            $parsed = date_create_immutable($value);

            return $parsed instanceof DateTimeImmutable ? $parsed : null;
        }

        return null;
    }
}
