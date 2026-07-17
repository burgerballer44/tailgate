<?php

use App\DTO\GamePointsContext;
use App\DTO\MissingPredictionContext;
use App\DTO\PlayerSeasonTotal;
use App\ScoringPolicies\PlacementPointsPolicy;
use Tests\TestCase;

uses(TestCase::class);

describe('placement points policy', function () {
    beforeEach(function () {
        $this->policy = new PlacementPointsPolicy();
    });

    test('exposes expected metadata', function () {
        expect(PlacementPointsPolicy::key())->toBe('placement-points')
            ->and(PlacementPointsPolicy::label())->toBe('Placement points (1st, 2nd, 3rd...)')
            ->and(PlacementPointsPolicy::description())->toContain('awarded placement points');
    });

    test('uses provided placement rank as game points', function () {
        $result = $this->policy->calculateGamePoints(new GamePointsContext(
            gameId: 4,
            playerId: 2,
            actualFollowedScore: 35,
            actualOpponentScore: 31,
            predictedFollowedScore: 34,
            predictedOpponentScore: 30,
            penaltyPoints: 0,
            placementRank: 1,
        ));

        expect($result->points)->toBe(1.0)
            ->and($result->calculationNotes)->toContain('placement-rank=1');
    });

    test('falls back to difference approximation when placement rank is unavailable', function () {
        $result = $this->policy->calculateGamePoints(new GamePointsContext(
            gameId: 4,
            playerId: 2,
            actualFollowedScore: 35,
            actualOpponentScore: 31,
            predictedFollowedScore: 33,
            predictedOpponentScore: 30,
            penaltyPoints: 0,
            placementRank: null,
        ));

        expect($result->points)->toBe(3.0)
            ->and($result->calculationNotes)->toContain('placement-rank=unavailable');
    });

    test('assigns missing prediction as trailing placement when configured', function () {
        $result = $this->policy->assignMissingPredictionPoints(new MissingPredictionContext(
            gameId: 4,
            playerId: 5,
            worstSubmittedGamePoints: 8,
            fallbackPoints: 14,
        ));

        expect($result->points)->toBe(9.0)
            ->and($result->calculationNotes)->toContain('counts-as-last-place=true');
    });

    test('uses previous rank then player id tie-breakers for deterministic ranking', function () {
        $left = new PlayerSeasonTotal(playerId: 20, playerName: 'Left', totalPoints: 13, previousRank: 4);
        $right = new PlayerSeasonTotal(playerId: 10, playerName: 'Right', totalPoints: 13, previousRank: 3);

        expect($this->policy->compareForRanking($left, $right))->toBeGreaterThan(0);
    });
});
