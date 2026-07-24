<?php

use App\DTO\PredictionPolicyContext;
use App\DTO\ValidatedPredictionData;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Enums\PredictionPolicyScope;
use App\Models\Season;
use App\PredictionPolicies\SeasonActivePolicy;

function makeSeasonContext(Game $game): PredictionPolicyContext
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
    $policy = new SeasonActivePolicy;

    expect($policy->key())->toBe('season-active');
    expect($policy->label())->toBe('Season active');
    expect($policy->description())->toBe('Predictions can only be submitted for games in active seasons.');
    expect($policy->scope())->toBe(PredictionPolicyScope::APP);
});

test('passes when season is active', function () {
    $season = Season::factory()->active()->create();
    $game = Game::factory()->create(['season_id' => $season->id]);

    $result = (new SeasonActivePolicy)->passes(makeSeasonContext($game));

    expect($result)->toBeTrue();
});

test('fails when season is inactive', function () {
    $season = Season::factory()->create(['active' => false]);
    $game = Game::factory()->create(['season_id' => $season->id]);

    $result = (new SeasonActivePolicy)->passes(makeSeasonContext($game));

    expect($result)->toBeFalse();
});

test('fails when no season relation is present in context', function () {
    $season = Season::factory()->active()->create();
    $game = Game::factory()->create(['season_id' => $season->id]);
    $game->setRelation('season', null);

    $result = (new SeasonActivePolicy)->passes(makeSeasonContext($game));

    expect($result)->toBeFalse();
});
