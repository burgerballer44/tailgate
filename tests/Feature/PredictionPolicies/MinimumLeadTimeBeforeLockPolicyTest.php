<?php

use App\DTO\PredictionPolicyContext;
use App\DTO\ValidatedPredictionData;
use App\Models\Enums\PredictionPolicyScope;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Season;
use App\PredictionPolicies\MinimumLeadTimeBeforeLockPolicy;

function makeLeadTimeContext(Game $game): PredictionPolicyContext
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
    $policy = new MinimumLeadTimeBeforeLockPolicy;

    expect($policy->key())->toBe('minimum-lead-time-before-lock');
    expect($policy->label())->toBe('Minimum lead time before lock');
    expect($policy->description())->toBe('Predictions must be submitted at least 30 minutes before the scheduled game start time.');
    expect($policy->scope())->toBe(PredictionPolicyScope::GROUP);
});

test('passes when the game starts more than 30 minutes in the future', function () {
    $game = Game::factory()->create([
        'season_id' => Season::factory()->active()->create()->id,
        'start_date_time' => now()->addMinutes(45)->format('Y-m-d H:i:s'),
        'start_time_tbd' => false,
    ]);

    $result = (new MinimumLeadTimeBeforeLockPolicy)->passes(makeLeadTimeContext($game));

    expect($result)->toBeTrue();
});

test('fails when the game starts within the minimum lead time window', function () {
    $game = Game::factory()->create([
        'season_id' => Season::factory()->active()->create()->id,
        'start_date_time' => now()->addMinutes(20)->format('Y-m-d H:i:s'),
        'start_time_tbd' => false,
    ]);

    $result = (new MinimumLeadTimeBeforeLockPolicy)->passes(makeLeadTimeContext($game));

    expect($result)->toBeFalse();
});

test('fails when the game started already', function () {
    $game = Game::factory()->create([
        'season_id' => Season::factory()->active()->create()->id,
        'start_date_time' => now()->subMinute()->format('Y-m-d H:i:s'),
        'start_time_tbd' => false,
    ]);

    $result = (new MinimumLeadTimeBeforeLockPolicy)->passes(makeLeadTimeContext($game));

    expect($result)->toBeFalse();
});
