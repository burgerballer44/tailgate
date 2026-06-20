<?php

use App\DTO\PredictionPolicyEvaluationResult;
use App\DTO\PredictionPolicyViolation;
use App\Models\PredictionPolicyScope;

describe('isValid', function () {
    test('returns true when no violations exist', function () {
        $result = new PredictionPolicyEvaluationResult();

        expect($result->isValid())->toBeTrue();
    });

    test('returns false when at least one violation exists', function () {
        $result = new PredictionPolicyEvaluationResult([
            new PredictionPolicyViolation(
                key: 'prediction-lock-time',
                label: 'Prediction lock time',
                description: 'Predictions cannot be submitted or updated after the scheduled game start time.',
                scope: PredictionPolicyScope::APP,
            ),
        ]);

        expect($result->isValid())->toBeFalse();
    });
});

describe('hasViolations', function () {
    test('returns false when no violations exist', function () {
        $result = new PredictionPolicyEvaluationResult();

        expect($result->hasViolations())->toBeFalse();
    });

    test('returns true when at least one violation exists', function () {
        $result = new PredictionPolicyEvaluationResult([
            new PredictionPolicyViolation(
                key: 'season-active',
                label: 'Season active',
                description: 'Predictions can only be submitted for games in active seasons.',
                scope: PredictionPolicyScope::APP,
            ),
        ]);

        expect($result->hasViolations())->toBeTrue();
    });
});

describe('summary', function () {
    test('returns success summary when there are no violations', function () {
        $result = new PredictionPolicyEvaluationResult();

        expect($result->summary())->toBe('Prediction submission is valid.');
    });

    test('returns failure summary for a single violation', function () {
        $result = new PredictionPolicyEvaluationResult([
            new PredictionPolicyViolation(
                key: 'season-active',
                label: 'Season active',
                description: 'Predictions can only be submitted for games in active seasons.',
                scope: PredictionPolicyScope::APP,
            ),
        ]);

        expect($result->summary())
            ->toBe('Prediction submission violates the following policies: Season active: Predictions can only be submitted for games in active seasons.');
    });

    test('joins multiple violation summaries with a pipe delimiter', function () {
        $result = new PredictionPolicyEvaluationResult([
            new PredictionPolicyViolation(
                key: 'prediction-lock-time',
                label: 'Prediction lock time',
                description: 'Predictions cannot be submitted or updated after the scheduled game start time.',
                scope: PredictionPolicyScope::APP,
            ),
            new PredictionPolicyViolation(
                key: 'minimum-lead-time-before-lock',
                label: 'Minimum lead time before lock',
                description: 'Predictions must be submitted at least 30 minutes before the scheduled game start time.',
                scope: PredictionPolicyScope::GROUP,
            ),
        ]);

        expect($result->summary())
            ->toBe('Prediction submission violates the following policies: Prediction lock time: Predictions cannot be submitted or updated after the scheduled game start time. | Minimum lead time before lock: Predictions must be submitted at least 30 minutes before the scheduled game start time.');
    });
});
