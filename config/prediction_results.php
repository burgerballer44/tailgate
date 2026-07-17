<?php

use App\ScoringPolicies\PlacementPointsPolicy;
use App\ScoringPolicies\PredictionDifferenceFromScorePointsPolicy;

return [
    'leaderboard' => [
        'required_columns' => [
            'player_name',
            'total_points',
            'current_rank',
            'previous_week_rank',
            'rank_change',
            'points_behind_leader',
        ],
    ],

    'raw_prediction_data' => [
        'required_game_columns' => [
            'week_sequence',
            'followed_team',
            'opponent_team',
            'actual_followed_score',
            'actual_opponent_score',
            'game_status',
        ],
        'required_player_columns' => [
            'player_name',
            'predicted_followed_score',
            'predicted_opponent_score',
            'penalty_points',
            'game_points',
        ],
    ],

    'missing_prediction' => [
        PredictionDifferenceFromScorePointsPolicy::key() => [
            'submitted_game_points_offset' => 7,
            // Edge case lock: if nobody submitted a prediction, use a deterministic baseline.
            'no_submissions_fallback_points' => 14,
        ],
        PlacementPointsPolicy::key() => [
            // Missing predictions in placement mode are treated as trailing entries.
            'missing_prediction_counts_as_last_place' => true,
        ],
    ],

    'ranking' => [
        PredictionDifferenceFromScorePointsPolicy::key() => [
            'tie_breakers' => [
                'previous_week_rank_asc',
                'player_id_asc',
            ],
        ],
        PlacementPointsPolicy::key() => [
            'tie_breakers' => [
                'previous_week_rank_asc',
                'player_id_asc',
            ],
        ],
    ],

    'membership' => [
        // Only approved members are eligible for leaderboard participation.
        'approved_members_only' => true,
        // Include game rows when kickoff is on/after joined_at and before left_at.
        'joined_at_inclusive' => true,
        'left_at_exclusive' => true,
    ],

    'group_context_change' => [
        // Recompute historical rows from canonical game + prediction records.
        'freeze_historical_rows' => false,
        // Scoring inclusion is evaluated per game against the persisted season-follow context.
        'evaluate_inclusion_per_game' => true,
    ],
];
