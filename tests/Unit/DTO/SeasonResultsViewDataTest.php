<?php

use App\DTO\GameRawPredictionData;
use App\DTO\GameRawPredictionPlayerRowData;
use App\DTO\PlayerLeaderboardRowData;
use App\DTO\SeasonResultsViewData;

describe('season results dto shapes', function () {
    test('serializes to expected payload shape with required fields', function () {
        $leaderboardRow = new PlayerLeaderboardRowData(
            playerId: 1,
            playerName: 'Player One',
            totalPoints: 19,
            rank: 1,
            previousRank: 2,
            rankChange: 1,
            pointsBehindLeader: 0,
        );

        $rawPlayerRow = new GameRawPredictionPlayerRowData(
            playerId: 1,
            playerName: 'Player One',
            predictedFollowedScore: 31,
            predictedOpponentScore: 27,
            penaltyPoints: 0,
            gamePoints: 4,
            calculationNotes: ['diff-followed=1', 'diff-opponent=3'],
        );

        $rawGameRow = new GameRawPredictionData(
            gameId: 10,
            weekLabel: 'Week 1',
            gameStatus: 'completed',
            followedTeam: 'Home Team',
            opponentTeam: 'Away Team',
            actualFollowedScore: 30,
            actualOpponentScore: 24,
            playerRows: [$rawPlayerRow],
        );

        $dto = new SeasonResultsViewData(
            groupId: 5,
            seasonId: 6,
            pointsPolicy: 'prediction-difference-from-score',
            generatedAt: new DateTimeImmutable('2026-07-11T00:00:00+00:00'),
            leaderboardRows: [$leaderboardRow],
            rawGameRows: [$rawGameRow],
            meta: ['warnings' => []],
        );

        $payload = $dto->toArray();

        expect(array_keys($payload))->toBe([
            'group_id',
            'season_id',
            'points_policy',
            'generated_at',
            'leaderboard_rows',
            'raw_game_rows',
            'meta',
        ]);

        expect($payload['leaderboard_rows'][0])->toBe([
            'player_id' => 1,
            'player_name' => 'Player One',
            'total_points' => 19.0,
            'rank' => 1,
            'previous_rank' => 2,
            'rank_change' => 1,
            'points_behind_leader' => 0.0,
        ]);

        expect($payload['raw_game_rows'][0]['player_rows'][0]['game_points'])->toBe(4.0);
    });

    test('enforces constructor scalar types for contract safety', function () {
        expect(fn () => new PlayerLeaderboardRowData(
            playerId: 'invalid',
            playerName: 'Player One',
            totalPoints: 19,
            rank: 1,
            previousRank: 2,
            rankChange: 1,
            pointsBehindLeader: 0,
        ))->toThrow(TypeError::class);
    });

    test('raw player row defaults calculation notes to empty array and supports nullable predictions', function () {
        $row = new GameRawPredictionPlayerRowData(
            playerId: 2,
            playerName: 'No Submission',
            predictedFollowedScore: null,
            predictedOpponentScore: null,
            penaltyPoints: 7,
            gamePoints: 14,
        );

        expect($row->toArray())->toBe([
            'player_id' => 2,
            'player_name' => 'No Submission',
            'predicted_followed_score' => null,
            'predicted_opponent_score' => null,
            'penalty_points' => 7,
            'game_points' => 14.0,
            'calculation_notes' => [],
        ]);
    });

    test('raw game row serializes nested player rows in deterministic order', function () {
        $first = new GameRawPredictionPlayerRowData(
            playerId: 1,
            playerName: 'First',
            predictedFollowedScore: 10,
            predictedOpponentScore: 7,
            penaltyPoints: 0,
            gamePoints: 3,
        );

        $second = new GameRawPredictionPlayerRowData(
            playerId: 2,
            playerName: 'Second',
            predictedFollowedScore: 13,
            predictedOpponentScore: 9,
            penaltyPoints: 1,
            gamePoints: 6,
        );

        $game = new GameRawPredictionData(
            gameId: 55,
            weekLabel: 'Week 3',
            gameStatus: 'completed',
            followedTeam: 'Followed',
            opponentTeam: 'Opponent',
            actualFollowedScore: 14,
            actualOpponentScore: 10,
            playerRows: [$first, $second],
        );

        $payload = $game->toArray();

        expect($payload['player_rows'][0]['player_name'])->toBe('First')
            ->and($payload['player_rows'][1]['player_name'])->toBe('Second');
    });
});
