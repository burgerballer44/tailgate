<?php

use App\DTO\SeasonResultsViewData;
use App\DTO\PlayerLeaderboardRowData;
use App\DTO\GameRawPredictionData;
use App\DTO\GameRawPredictionPlayerRowData;
use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupSeasonFollow;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Season;
use App\Models\Team;
use App\Services\Contracts\GroupSeasonLeaderboardServiceInterface;

describe('group season leaderboard service contract', function () {
    test('returns dto shape with query orchestration metadata', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'prediction_scoring_policy' => 'placement-points',
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 24,
            'away_team_score' => 17,
            'start_date_time' => '2026-09-01 12:00:00',
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
            'created_at' => '2026-08-01 00:00:00',
        ]);

        $player = Player::factory()->create(['member_id' => $member->id]);

        Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);

        expect($result)->toBeInstanceOf(SeasonResultsViewData::class)
            ->and($result->groupId)->toBe($group->id)
            ->and($result->seasonId)->toBe($season->id)
            ->and($result->pointsPolicy)->toBe('placement-points')
            ->and($result->generatedAt)->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($result->leaderboardRows)->toBeArray()
            ->and($result->rawGameRows)->toBeArray()
            ->and($result->meta['status'])->toBe('query-orchestrated')
            ->and($result->meta['total_games_loaded'])->toBe(1)
            ->and($result->meta['total_predictions_loaded'])->toBe(1)
            ->and($result->meta['total_raw_game_rows'])->toBe(1)
            ->and($result->meta['game_ids'])->toBe([$game->id])
            ->and($result->meta['eligible_player_ids_by_game'][(string) $game->id])->toBe([$player->id]);

        expect($result->rawGameRows)->toHaveCount(1)
            ->and($result->rawGameRows[0])->toBeInstanceOf(GameRawPredictionData::class)
            ->and($result->rawGameRows[0]->playerRows[0])->toBeInstanceOf(GameRawPredictionPlayerRowData::class)
            ->and($result->rawGameRows[0]->playerRows[0]->penaltyPoints)->toBe(0)
            ->and($result->rawGameRows[0]->playerRows[0]->calculationNotes)->not->toBe([]);
    });

    test('applies season and follow scoping to loaded games', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $otherSeason = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();
        $unfollowedTeam = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        $included = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 31,
            'away_team_score' => 30,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $unfollowedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 10,
            'away_team_score' => 7,
        ]);

        Game::factory()->create([
            'season_id' => $otherSeason->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 10,
            'away_team_score' => 7,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);

        expect($result->meta['game_ids'])->toBe([$included->id])
            ->and($result->meta['total_games_loaded'])->toBe(1);
    });

    test('excludes non-scorable games and reports exclusion reasons', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        $scorable = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 17,
            'away_team_score' => 14,
        ]);

        $excluded = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 'PST',
            'away_team_score' => 'PST',
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);

        expect($result->meta['game_ids'])->toBe([$scorable->id])
            ->and($result->meta['excluded_games'])->toContain([
                'game_id' => $excluded->id,
                'reason' => 'non_scorable_status_or_missing_score',
            ]);
    });

    test('uses membership join window to include players only from eligible games', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        $beforeJoinGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 10,
            'away_team_score' => 9,
            'start_date_time' => '2026-09-01 12:00:00',
        ]);

        $afterJoinGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 13,
            'away_team_score' => 9,
            'start_date_time' => '2026-09-15 12:00:00',
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
            'created_at' => '2026-09-10 00:00:00',
        ]);

        $player = Player::factory()->create(['member_id' => $member->id]);

        $result = $service->buildSeasonResults($group->id, $season->id);

        expect($result->meta['eligible_player_ids_by_game'][(string) $beforeJoinGame->id])->toBe([])
            ->and($result->meta['eligible_player_ids_by_game'][(string) $afterJoinGame->id])->toBe([$player->id]);
    });

    test('preserves departed member historical eligibility and excludes post-leave games', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        $beforeLeaveGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 24,
            'away_team_score' => 17,
            'start_date_time' => '2026-09-05 12:00:00',
        ]);

        $afterLeaveGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 27,
            'away_team_score' => 20,
            'start_date_time' => '2026-09-20 12:00:00',
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::LEFT->value,
            'created_at' => '2026-08-01 00:00:00',
            'left_at' => '2026-09-12 00:00:00',
        ]);

        $player = Player::factory()->create(['member_id' => $member->id]);

        Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $beforeLeaveGame->id,
            'home_team_prediction' => 24,
            'away_team_prediction' => 17,
        ]);

        Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $afterLeaveGame->id,
            'home_team_prediction' => 27,
            'away_team_prediction' => 20,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);

        expect($result->meta['eligible_player_ids_by_game'][(string) $beforeLeaveGame->id])->toBe([$player->id])
            ->and($result->meta['eligible_player_ids_by_game'][(string) $afterLeaveGame->id])->toBe([]);
    });

    test('propagates asOfGameId and filters games after the marker', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
        ]);

        $first = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 21,
            'away_team_score' => 20,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 24,
            'away_team_score' => 23,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id, $first->id);

        expect($result->meta['as_of_game_id'])->toBe($first->id)
            ->and($result->meta['game_ids'])->toBe([$first->id]);
    });

    test('aggregates prediction-difference totals and computes leaderboard rank fields', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'prediction_scoring_policy' => 'prediction-difference-from-score',
        ]);

        $gameOne = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 20,
            'away_team_score' => 10,
            'start_date_time' => '2026-09-01 12:00:00',
        ]);

        $gameTwo = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 14,
            'away_team_score' => 7,
            'start_date_time' => '2026-09-08 12:00:00',
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
            'created_at' => '2026-08-01 00:00:00',
        ]);

        $playerOne = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'Alpha']);
        $playerTwo = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'Bravo']);

        // Game 1 differences: Alpha=10, Bravo=2.
        Prediction::factory()->create([
            'player_id' => $playerOne->id,
            'game_id' => $gameOne->id,
            'home_team_prediction' => 30,
            'away_team_prediction' => 10,
        ]);
        Prediction::factory()->create([
            'player_id' => $playerTwo->id,
            'game_id' => $gameOne->id,
            'home_team_prediction' => 21,
            'away_team_prediction' => 11,
        ]);

        // Game 2 differences: Alpha=0, Bravo=19.
        Prediction::factory()->create([
            'player_id' => $playerOne->id,
            'game_id' => $gameTwo->id,
            'home_team_prediction' => 14,
            'away_team_prediction' => 7,
        ]);
        Prediction::factory()->create([
            'player_id' => $playerTwo->id,
            'game_id' => $gameTwo->id,
            'home_team_prediction' => 30,
            'away_team_prediction' => 10,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);
        $rows = collect($result->leaderboardRows)
            ->map(fn (PlayerLeaderboardRowData $row): array => $row->toArray())
            ->keyBy('player_id');

        expect($rows[$playerOne->id]['total_points'])->toBe(10.0)
            ->and($rows[$playerOne->id]['rank'])->toBe(1)
            ->and($rows[$playerOne->id]['previous_rank'])->toBe(2)
            ->and($rows[$playerOne->id]['rank_change'])->toBe(1)
            ->and($rows[$playerOne->id]['points_behind_leader'])->toBe(0.0)
            ->and($rows[$playerTwo->id]['total_points'])->toBe(21.0)
            ->and($rows[$playerTwo->id]['rank'])->toBe(2)
            ->and($rows[$playerTwo->id]['previous_rank'])->toBe(1)
            ->and($rows[$playerTwo->id]['rank_change'])->toBe(-1)
            ->and($rows[$playerTwo->id]['points_behind_leader'])->toBe(11.0)
            ->and($result->meta['total_leaderboard_rows'])->toBe(2);
    });

    test('uses the previous week snapshot instead of the previous game snapshot for rank change', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'prediction_scoring_policy' => 'prediction-difference-from-score',
        ]);

        $weekOneGameOne = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 20,
            'away_team_score' => 10,
            'start_date_time' => '2026-09-01 12:00:00',
        ]);

        $weekOneGameTwo = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 14,
            'away_team_score' => 7,
            'start_date_time' => '2026-09-03 12:00:00',
        ]);

        $weekTwoGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 28,
            'away_team_score' => 21,
            'start_date_time' => '2026-09-10 12:00:00',
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
            'created_at' => '2026-08-01 00:00:00',
        ]);

        $alpha = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'Alpha']);
        $bravo = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'Bravo']);

        Prediction::factory()->create([
            'player_id' => $alpha->id,
            'game_id' => $weekOneGameOne->id,
            'home_team_prediction' => 20,
            'away_team_prediction' => 10,
        ]);
        Prediction::factory()->create([
            'player_id' => $bravo->id,
            'game_id' => $weekOneGameOne->id,
            'home_team_prediction' => 24,
            'away_team_prediction' => 10,
        ]);

        Prediction::factory()->create([
            'player_id' => $alpha->id,
            'game_id' => $weekOneGameTwo->id,
            'home_team_prediction' => 20,
            'away_team_prediction' => 10,
        ]);
        Prediction::factory()->create([
            'player_id' => $bravo->id,
            'game_id' => $weekOneGameTwo->id,
            'home_team_prediction' => 14,
            'away_team_prediction' => 7,
        ]);

        Prediction::factory()->create([
            'player_id' => $alpha->id,
            'game_id' => $weekTwoGame->id,
            'home_team_prediction' => 28,
            'away_team_prediction' => 21,
        ]);
        Prediction::factory()->create([
            'player_id' => $bravo->id,
            'game_id' => $weekTwoGame->id,
            'home_team_prediction' => 42,
            'away_team_prediction' => 30,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);
        $rows = collect($result->leaderboardRows)
            ->map(fn (PlayerLeaderboardRowData $row): array => $row->toArray())
            ->keyBy('player_id');

        expect($rows[$alpha->id]['rank'])->toBe(1)
            ->and($rows[$alpha->id]['previous_rank'])->toBe(2)
            ->and($rows[$alpha->id]['rank_change'])->toBe(1)
            ->and($rows[$bravo->id]['rank'])->toBe(2)
            ->and($rows[$bravo->id]['previous_rank'])->toBe(1)
            ->and($rows[$bravo->id]['rank_change'])->toBe(-1);
    });

    test('applies placement policy ranks and missing prediction trailing placement points', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'prediction_scoring_policy' => 'placement-points',
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 28,
            'away_team_score' => 21,
            'start_date_time' => '2026-09-01 12:00:00',
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
            'created_at' => '2026-08-01 00:00:00',
        ]);

        $first = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'First']);
        $second = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'Second']);
        $missing = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'Missing']);

        // Differences: First=1 (rank 1), Second=5 (rank 2), Missing=>rank 3 via trailing rule.
        Prediction::factory()->create([
            'player_id' => $first->id,
            'game_id' => $game->id,
            'home_team_prediction' => 29,
            'away_team_prediction' => 21,
        ]);
        Prediction::factory()->create([
            'player_id' => $second->id,
            'game_id' => $game->id,
            'home_team_prediction' => 33,
            'away_team_prediction' => 21,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);
        $rows = collect($result->leaderboardRows)
            ->map(fn (PlayerLeaderboardRowData $row): array => $row->toArray())
            ->keyBy('player_id');

        expect($rows[$first->id]['total_points'])->toBe(1.0)
            ->and($rows[$first->id]['rank'])->toBe(1)
            ->and($rows[$second->id]['total_points'])->toBe(2.0)
            ->and($rows[$second->id]['rank'])->toBe(2)
            ->and($rows[$missing->id]['total_points'])->toBe(3.0)
            ->and($rows[$missing->id]['rank'])->toBe(3)
            ->and($rows[$missing->id]['points_behind_leader'])->toBe(2.0);

        $rawGame = $result->rawGameRows[0];
        $rawRows = collect($rawGame->playerRows)
            ->map(fn (GameRawPredictionPlayerRowData $row): array => $row->toArray())
            ->keyBy('player_id');

        expect($rawGame->actualFollowedScore)->toBe(28)
            ->and($rawGame->actualOpponentScore)->toBe(21)
            ->and($rawRows[$first->id]['game_points'])->toBe(1.0)
            ->and($rawRows[$second->id]['game_points'])->toBe(2.0)
            ->and($rawRows[$missing->id]['predicted_followed_score'])->toBeNull()
            ->and($rawRows[$missing->id]['predicted_opponent_score'])->toBeNull()
            ->and($rawRows[$missing->id]['game_points'])->toBe(3.0)
            ->and($rawRows[$missing->id]['calculation_notes'])->toContain('missing-prediction=true');
    });

    test('includes calculation notes in raw rows for prediction-difference policy', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'prediction_scoring_policy' => 'prediction-difference-from-score',
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 30,
            'away_team_score' => 20,
            'start_date_time' => '2026-09-01 12:00:00',
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
            'created_at' => '2026-08-01 00:00:00',
        ]);

        $player = Player::factory()->create(['member_id' => $member->id]);

        Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 28,
            'away_team_prediction' => 21,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);
        $rawRow = $result->rawGameRows[0]->playerRows[0]->toArray();

        expect($rawRow['calculation_notes'])->toContain('diff-followed=2')
            ->and($rawRow['calculation_notes'])->toContain('diff-opponent=1')
            ->and($rawRow['penalty_points'])->toBe(0);
    });

    test('includes non-scorable games in raw rows with derived status and null final scores', function () {
        $service = app(GroupSeasonLeaderboardServiceInterface::class);
        $group = Group::factory()->create();
        $season = Season::factory()->active()->create();
        $followedTeam = Team::factory()->create();
        $opponent = Team::factory()->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
        ]);

        GroupSeasonFollow::factory()->create([
            'group_id' => $group->id,
            'season_id' => $season->id,
            'prediction_scoring_policy' => 'prediction-difference-from-score',
        ]);

        $completed = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 17,
            'away_team_score' => 14,
            'start_date_time' => '2026-09-01 12:00:00',
        ]);

        $postponed = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'home_team_score' => 'PST',
            'away_team_score' => 'PST',
            'start_date_time' => '2026-09-08 12:00:00',
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
            'created_at' => '2026-08-01 00:00:00',
        ]);

        $player = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'Player']);

        Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $completed->id,
            'home_team_prediction' => 20,
            'away_team_prediction' => 10,
        ]);

        Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $postponed->id,
            'home_team_prediction' => 21,
            'away_team_prediction' => 17,
        ]);

        $result = $service->buildSeasonResults($group->id, $season->id);
        $rawGames = collect($result->rawGameRows)
            ->map(fn (GameRawPredictionData $row): array => $row->toArray())
            ->keyBy('game_id');

        expect($rawGames->keys()->all())->toBe([$completed->id, $postponed->id])
            ->and($rawGames[$completed->id]['game_status'])->toBe('completed')
            ->and($rawGames[$completed->id]['week_label'])->toBe('Week 1')
            ->and($rawGames[$postponed->id]['game_status'])->toBe('postponed')
            ->and($rawGames[$postponed->id]['week_label'])->toBe('Week 2')
            ->and($rawGames[$postponed->id]['actual_followed_score'])->toBeNull()
            ->and($rawGames[$postponed->id]['actual_opponent_score'])->toBeNull();
    });
});
