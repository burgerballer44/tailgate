<?php

use App\ScoringPolicies\PlacementPointsPolicy;
use App\ScoringPolicies\PredictionDifferenceFromScorePointsPolicy;
use Tests\TestCase;

uses(TestCase::class);

describe('prediction results domain rules config', function () {
    test('defines required leaderboard columns', function () {
        expect(config('prediction_results.leaderboard.required_columns'))->toBe([
            'player_name',
            'total_points',
            'current_rank',
            'previous_week_rank',
            'rank_change',
            'points_behind_leader',
        ]);
    });

    test('defines required raw prediction data columns', function () {
        expect(config('prediction_results.raw_prediction_data.required_game_columns'))->toBe([
            'week_sequence',
            'followed_team',
            'opponent_team',
            'actual_followed_score',
            'actual_opponent_score',
            'game_status',
        ]);

        expect(config('prediction_results.raw_prediction_data.required_player_columns'))->toBe([
            'player_name',
            'predicted_followed_score',
            'predicted_opponent_score',
            'penalty_points',
            'game_points',
        ]);
    });

    test('locks missing prediction edge case behavior', function () {
        expect(config('prediction_results.missing_prediction.'.PredictionDifferenceFromScorePointsPolicy::key().'.submitted_game_points_offset'))
            ->toBe(7)
            ->and(config('prediction_results.missing_prediction.'.PredictionDifferenceFromScorePointsPolicy::key().'.no_submissions_fallback_points'))
            ->toBe(14)
            ->and(config('prediction_results.missing_prediction.'.PlacementPointsPolicy::key().'.missing_prediction_counts_as_last_place'))
            ->toBeTrue();
    });

    test('locks deterministic tie breakers for all supported scoring policies', function () {
        expect(config('prediction_results.ranking.'.PredictionDifferenceFromScorePointsPolicy::key().'.tie_breakers'))
            ->toBe([
                'previous_week_rank_asc',
                'player_id_asc',
            ])
            ->and(config('prediction_results.ranking.'.PlacementPointsPolicy::key().'.tie_breakers'))
            ->toBe([
                'previous_week_rank_asc',
                'player_id_asc',
            ]);
    });

    test('locks membership and context change rules', function () {
        expect(config('prediction_results.membership.approved_members_only'))
            ->toBeTrue()
            ->and(config('prediction_results.membership.joined_at_inclusive'))
            ->toBeTrue()
            ->and(config('prediction_results.membership.left_at_exclusive'))
            ->toBeTrue()
            ->and(config('prediction_results.group_context_change.freeze_historical_rows'))
            ->toBeFalse()
            ->and(config('prediction_results.group_context_change.evaluate_inclusion_per_game'))
            ->toBeTrue();
    });
});
