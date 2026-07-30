<?php

use App\DTO\GamePointsContext;
use App\DTO\MissingPredictionContext;
use App\DTO\PlayerSeasonTotal;
use App\ScoringPolicies\PredictionDifferenceFromScorePointsPolicy;
use Tests\TestCase;

uses(TestCase::class);

describe('prediction difference from score points policy', function () {
    beforeEach(function () {
        $this->policy = new PredictionDifferenceFromScorePointsPolicy;
    });

    test('exposes expected metadata', function () {
        expect(PredictionDifferenceFromScorePointsPolicy::key())->toBe('prediction-difference-from-score')
            ->and(PredictionDifferenceFromScorePointsPolicy::label())->toBe('Prediction difference from score (lowest total wins)')
            ->and(PredictionDifferenceFromScorePointsPolicy::description())->toContain('sum of absolute differences');
    });

    test('calculates points from absolute score differences plus penalties', function () {
        $result = $this->policy->calculateGamePoints(new GamePointsContext(
            gameId: 3,
            playerId: 7,
            actualFollowedScore: 28,
            actualOpponentScore: 21,
            predictedFollowedScore: 30,
            predictedOpponentScore: 20,
            penaltyPoints: 2,
            placementRank: null,
        ));

        expect($result->points)->toBe(5.0)
            ->and($result->penaltyPoints)->toBe(2)
            ->and($result->calculationNotes)->toContain('diff-followed=2')
            ->and($result->calculationNotes)->toContain('diff-opponent=1');
    });

    test('assigns missing points as worst submitted plus configured offset', function () {
        $result = $this->policy->assignMissingPredictionPoints(new MissingPredictionContext(
            gameId: 3,
            playerId: 9,
            worstSubmittedGamePoints: 18,
            fallbackPoints: 14,
        ));

        expect($result->points)->toBe(25.0);
    });

    test('uses deterministic fallback baseline when no submitted predictions exist', function () {
        $result = $this->policy->assignMissingPredictionPoints(new MissingPredictionContext(
            gameId: 3,
            playerId: 9,
            worstSubmittedGamePoints: null,
            fallbackPoints: 14,
        ));

        expect($result->points)->toBe(14.0)
            ->and($result->calculationNotes)->toContain('worst-submitted=none');
    });

    test('applies configured tie-breakers for deterministic ranking', function () {
        $left = new PlayerSeasonTotal(playerId: 11, playerName: 'Left', totalPoints: 20, previousRank: 2);
        $right = new PlayerSeasonTotal(playerId: 9, playerName: 'Right', totalPoints: 20, previousRank: 1);

        expect($this->policy->compareForRanking($left, $right))->toBeGreaterThan(0);
    });
});
