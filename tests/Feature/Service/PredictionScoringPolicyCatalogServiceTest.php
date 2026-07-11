<?php

use App\Services\Contracts\PredictionScoringPolicyCatalogInterface;

beforeEach(function () {
    $this->catalog = app(PredictionScoringPolicyCatalogInterface::class);
});

describe('prediction scoring policy catalog', function () {
    test('returns all configured policy options with expected metadata', function () {
        $options = $this->catalog->options();

        expect($options)->toHaveCount(2);

        $byKey = collect($options)->keyBy('key');

        expect($byKey->keys()->all())->toBe([
            'prediction-difference-from-score',
            'placement-points',
        ]);

        expect($byKey['prediction-difference-from-score']->label)
            ->toBe('Prediction difference from score (lowest total wins)');

        expect($byKey['placement-points']->label)
            ->toBe('Placement points (1st, 2nd, 3rd...)');
    });

    test('returns exactly one default option and it matches default key', function () {
        $options = collect($this->catalog->options());

        $defaultOptions = $options->filter(fn ($option) => $option->is_default);

        expect($defaultOptions)->toHaveCount(1);
        expect($defaultOptions->first()->key)->toBe($this->catalog->defaultKey());
    });

    test('keys returns all supported policy keys in deterministic order', function () {
        expect($this->catalog->keys())->toBe([
            'prediction-difference-from-score',
            'placement-points',
        ]);
    });

    test('isValid returns true for known policy keys and false for unknown keys', function () {
        expect($this->catalog->isValid('prediction-difference-from-score'))->toBeTrue();
        expect($this->catalog->isValid('placement-points'))->toBeTrue();
        expect($this->catalog->isValid('invalid-policy'))->toBeFalse();
    });
});
