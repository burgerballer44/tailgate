<?php

use App\DTO\PredictionPolicyContext;
use App\DTO\ValidatedPredictionData;
use App\Models\Enums\PredictionPolicyScope;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Season;
use App\PredictionPolicies\PredictionLockTimePolicy;

function makeLockTimeContext(Game $game): PredictionPolicyContext
{
    $group = Group::factory()->create();
    $member = Member::factory()->create(['group_id' => $group->id]);
    $player = Player::factory()->create(['member_id' => $member->id]);

    return new PredictionPolicyContext(
        player: $player,
        group: $group,
        game: $game,
        submission: ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 10,
            'away_team_prediction' => 7,
        ]),
    );
}

test('returns expected metadata', function () {
    $policy = new PredictionLockTimePolicy;

    expect($policy->key())->toBe('prediction-lock-time');
    expect($policy->label())->toBe('Prediction lock time');
    expect($policy->description())->toBe('Predictions cannot be submitted or updated after the scheduled game start time.');
    expect($policy->scope())->toBe(PredictionPolicyScope::APP);
});

test('passes when game start time is in the future', function () {
    $game = Game::factory()->create([
        'season_id' => Season::factory()->active()->create()->id,
        'start_date_time' => now()->addHour()->format('Y-m-d H:i:s'),
        'start_time_tbd' => false,
    ]);

    $result = (new PredictionLockTimePolicy)->passes(makeLockTimeContext($game));

    expect($result)->toBeTrue();
});

test('fails when game start time has passed', function () {
    $game = Game::factory()->create([
        'season_id' => Season::factory()->active()->create()->id,
        'start_date_time' => now()->subHour()->format('Y-m-d H:i:s'),
        'start_time_tbd' => false,
    ]);

    $result = (new PredictionLockTimePolicy)->passes(makeLockTimeContext($game));

    expect($result)->toBeFalse();
});

test('passes for tbd games on the same day', function () {
    $game = Game::factory()->create([
        'season_id' => Season::factory()->active()->create()->id,
        'start_date_time' => now()->format('Y-m-d 00:00:00'),
        'start_time_tbd' => true,
    ]);

    $result = (new PredictionLockTimePolicy)->passes(makeLockTimeContext($game));

    expect($result)->toBeTrue();
});

test('fails for tbd games on a prior day', function () {
    $game = Game::factory()->create([
        'season_id' => Season::factory()->active()->create()->id,
        'start_date_time' => now()->subDay()->format('Y-m-d 00:00:00'),
        'start_time_tbd' => true,
    ]);

    $result = (new PredictionLockTimePolicy)->passes(makeLockTimeContext($game));

    expect($result)->toBeFalse();
});
