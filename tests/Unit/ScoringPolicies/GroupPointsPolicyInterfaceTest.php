<?php

use App\DTO\GamePointsContext;
use App\DTO\MissingPredictionContext;
use App\DTO\PlayerGamePointsResult;
use App\DTO\PlayerSeasonTotal;
use App\ScoringPolicies\Contracts\GroupPointsPolicyInterface;

describe('group points policy interface contract', function () {
    test('supports calculation contexts and ranking comparison signatures', function () {
        $policy = new class implements GroupPointsPolicyInterface
        {
            public static function key(): string
            {
                return 'dummy-policy';
            }

            public static function label(): string
            {
                return 'Dummy policy';
            }

            public static function description(): string
            {
                return 'Dummy policy for interface contract verification.';
            }

            public function calculateGamePoints(GamePointsContext $context): PlayerGamePointsResult
            {
                return new PlayerGamePointsResult(
                    playerId: $context->playerId,
                    gameId: $context->gameId,
                    points: 0,
                    penaltyPoints: $context->penaltyPoints,
                    calculationNotes: ['dummy-calc'],
                );
            }

            public function assignMissingPredictionPoints(MissingPredictionContext $context): PlayerGamePointsResult
            {
                return new PlayerGamePointsResult(
                    playerId: $context->playerId,
                    gameId: $context->gameId,
                    points: $context->fallbackPoints,
                    penaltyPoints: 0,
                    calculationNotes: ['dummy-missing'],
                );
            }

            public function compareForRanking(PlayerSeasonTotal $left, PlayerSeasonTotal $right): int
            {
                return $left->totalPoints <=> $right->totalPoints;
            }
        };

        $gameResult = $policy->calculateGamePoints(new GamePointsContext(
            gameId: 9,
            playerId: 3,
            actualFollowedScore: 28,
            actualOpponentScore: 21,
            predictedFollowedScore: 27,
            predictedOpponentScore: 20,
            penaltyPoints: 0,
            placementRank: null,
        ));

        $missingResult = $policy->assignMissingPredictionPoints(new MissingPredictionContext(
            gameId: 9,
            playerId: 7,
            worstSubmittedGamePoints: 11,
            fallbackPoints: 14,
        ));

        $comparison = $policy->compareForRanking(
            new PlayerSeasonTotal(playerId: 1, playerName: 'A', totalPoints: 18, previousRank: 2),
            new PlayerSeasonTotal(playerId: 2, playerName: 'B', totalPoints: 21, previousRank: 1),
        );

        expect($policy::key())->toBe('dummy-policy')
            ->and($gameResult)->toBeInstanceOf(PlayerGamePointsResult::class)
            ->and($missingResult->points)->toBe(14.0)
            ->and($comparison)->toBeLessThan(0);
    });
});
