<?php

use App\DTO\PredictionPolicyContext;
use App\DTO\ValidatedPredictionData;
use App\Models\Enums\PredictionPolicyScope;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Season;
use App\PredictionPolicies\UniqueGroupPredictionPolicy;

function makeUniqueGroupContext(Group $group, Player $player, Game $game, ?Prediction $prediction = null): PredictionPolicyContext
{
    return new PredictionPolicyContext(
        player: $player,
        group: $group,
        game: $game,
        submission: ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 10,
            'away_team_prediction' => 7,
        ]),
        prediction: $prediction,
    );
}

test('returns expected metadata', function () {
    $policy = new UniqueGroupPredictionPolicy;

    expect($policy->key())->toBe('group-unique-prediction');
    expect($policy->label())->toBe('Unique group prediction');
    expect($policy->description())->toBe('When enabled for a group, only one prediction for a game may exist within that group.');
    expect($policy->scope())->toBe(PredictionPolicyScope::GROUP);
});

test('passes when no other prediction exists in the group for the game', function () {
    $group = Group::factory()->create();
    $season = Season::factory()->active()->create();
    $game = Game::factory()->create(['season_id' => $season->id]);
    $member = Member::factory()->create(['group_id' => $group->id]);
    $player = Player::factory()->create(['member_id' => $member->id]);

    $result = (new UniqueGroupPredictionPolicy)->passes(makeUniqueGroupContext($group, $player, $game));

    expect($result)->toBeTrue();
});

test('fails when another prediction exists in the same group for the same game', function () {
    $group = Group::factory()->create();
    $season = Season::factory()->active()->create();
    $game = Game::factory()->create(['season_id' => $season->id]);

    $firstMember = Member::factory()->create(['group_id' => $group->id]);
    $firstPlayer = Player::factory()->create(['member_id' => $firstMember->id]);
    Prediction::factory()->create(['player_id' => $firstPlayer->id, 'game_id' => $game->id]);

    $secondMember = Member::factory()->create(['group_id' => $group->id]);
    $secondPlayer = Player::factory()->create(['member_id' => $secondMember->id]);

    $result = (new UniqueGroupPredictionPolicy)->passes(makeUniqueGroupContext($group, $secondPlayer, $game));

    expect($result)->toBeFalse();
});

test('passes when prediction exists in a different group for the same game', function () {
    $season = Season::factory()->active()->create();
    $game = Game::factory()->create(['season_id' => $season->id]);

    $otherGroup = Group::factory()->create();
    $otherMember = Member::factory()->create(['group_id' => $otherGroup->id]);
    $otherPlayer = Player::factory()->create(['member_id' => $otherMember->id]);
    Prediction::factory()->create(['player_id' => $otherPlayer->id, 'game_id' => $game->id]);

    $group = Group::factory()->create();
    $member = Member::factory()->create(['group_id' => $group->id]);
    $player = Player::factory()->create(['member_id' => $member->id]);

    $result = (new UniqueGroupPredictionPolicy)->passes(makeUniqueGroupContext($group, $player, $game));

    expect($result)->toBeTrue();
});

test('passes when updating and the only existing prediction is itself', function () {
    $group = Group::factory()->create();
    $season = Season::factory()->active()->create();
    $game = Game::factory()->create(['season_id' => $season->id]);
    $member = Member::factory()->create(['group_id' => $group->id]);
    $player = Player::factory()->create(['member_id' => $member->id]);
    $prediction = Prediction::factory()->create(['player_id' => $player->id, 'game_id' => $game->id]);

    $result = (new UniqueGroupPredictionPolicy)->passes(makeUniqueGroupContext($group, $player, $game, $prediction));

    expect($result)->toBeTrue();
});

test('fails when updating and another prediction exists in the same group', function () {
    $group = Group::factory()->create();
    $season = Season::factory()->active()->create();
    $game = Game::factory()->create(['season_id' => $season->id]);

    $firstMember = Member::factory()->create(['group_id' => $group->id]);
    $firstPlayer = Player::factory()->create(['member_id' => $firstMember->id]);
    $existing = Prediction::factory()->create(['player_id' => $firstPlayer->id, 'game_id' => $game->id]);

    $secondMember = Member::factory()->create(['group_id' => $group->id]);
    $secondPlayer = Player::factory()->create(['member_id' => $secondMember->id]);
    $updating = Prediction::factory()->create(['player_id' => $secondPlayer->id, 'game_id' => $game->id]);

    $result = (new UniqueGroupPredictionPolicy)->passes(makeUniqueGroupContext($group, $secondPlayer, $game, $updating));

    expect($existing->id)->not->toBe($updating->id);
    expect($result)->toBeFalse();
});
