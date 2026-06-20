<?php

use App\DTO\ValidatedPredictionData;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\PredictionPolicyScope;
use App\Models\Season;
use App\PredictionPolicies\MinimumLeadTimeBeforeLockPolicy;
use App\PredictionPolicies\PredictionLockTimePolicy;
use App\PredictionPolicies\SeasonActivePolicy;
use App\PredictionPolicies\UniqueGroupPredictionPolicy;
use App\Services\PredictionPolicyEvaluatorService;

describe('evaluate', function () {
    test('loads missing member and group relations before evaluation continues', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This proves evaluate() can be called with a plain player model and still resolve group-level context.
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        // Re-fetch to mimic call sites that do not eager load relations first.
        $player = Player::query()->findOrFail($player->id);

        expect($player->relationLoaded('member'))->toBeFalse();

        $result = $service->evaluate($player, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 20,
            'away_team_prediction' => 17,
        ]));

        expect($result->isValid())->toBeTrue();
        expect($player->relationLoaded('member'))->toBeTrue();
        expect($player->member)->not->toBeNull();
        expect($player->member->relationLoaded('group'))->toBeTrue();
        expect($player->member->group?->id)->toBe($group->id);
    });

    test('uses prediction game when evaluating an update submission', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This verifies the update branch in resolveGame() where the prediction model is authoritative.
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);
        $prediction = Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
        ]);

        $result = $service->evaluate($player, ValidatedPredictionData::fromArray([
            'game_id' => 999999,
            'home_team_prediction' => 27,
            'away_team_prediction' => 20,
        ]), $prediction);

        expect($result->isValid())->toBeTrue();
        expect($result->violations)->toBe([]);
    });

    test('throws when submission game cannot be found', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This checks the create-submission branch in resolveGame() when game lookup fails.
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        $evaluate = fn () => $service->evaluate($player, ValidatedPredictionData::fromArray([
            'game_id' => 999999,
            'home_team_prediction' => 21,
            'away_team_prediction' => 14,
        ]));

        expect($evaluate)->toThrow(\RuntimeException::class, 'Prediction policies require a valid game.');
    });

    test('throws when player does not resolve to a group', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This covers the group guard immediately after game resolution.
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        $player = new Player([
            'player_name' => 'Ungrouped Player',
            'member_id' => null,
        ]);
        $player->setRelation('member', null);

        $evaluate = fn () => $service->evaluate($player, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 21,
            'away_team_prediction' => 14,
        ]));

        expect($evaluate)->toThrow(\RuntimeException::class, 'Prediction policies require the player to belong to a group.');
    });

    test('returns valid result when app and enabled group policies pass', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This is the baseline successful flow where no violations should be returned.
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        $result = $service->evaluate($player, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 24,
            'away_team_prediction' => 17,
        ]));

        expect($result->isValid())->toBeTrue();
        expect($result->violations)->toBe([]);
    });

    test('adds app-level violations in app rule order when app checks fail', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This verifies that both app policies contribute violations in deterministic order.
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);
        $season = Season::factory()->create(['active' => false]);
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->subDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        $result = $service->evaluate($player, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 10,
            'away_team_prediction' => 7,
        ]));

        expect($result->isValid())->toBeFalse();
        expect($result->violations)->toHaveCount(2);
        expect($result->violations[0]->key)->toBe((new PredictionLockTimePolicy)->key());
        expect($result->violations[1]->key)->toBe((new SeasonActivePolicy)->key());
    });

    test('skips optional group checks when no group policies are enabled', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This proves duplicates do not fail unless the unique-group policy is enabled.
        $group = Group::factory()->create([
            'enabled_prediction_policies' => [],
        ]);
        $firstMember = Member::factory()->create(['group_id' => $group->id]);
        $secondMember = Member::factory()->create(['group_id' => $group->id]);
        $firstPlayer = Player::factory()->create(['member_id' => $firstMember->id]);
        $secondPlayer = Player::factory()->create(['member_id' => $secondMember->id]);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        Prediction::factory()->create([
            'player_id' => $firstPlayer->id,
            'game_id' => $game->id,
        ]);

        $result = $service->evaluate($secondPlayer, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 24,
            'away_team_prediction' => 21,
        ]));

        expect($result->isValid())->toBeTrue();
        expect($result->violations)->toBe([]);
    });

    test('adds unique-group violation when unique group policy is enabled and duplicate exists', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This verifies the first group-level policy path when enabled.
        $group = Group::factory()->create([
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]);
        $firstMember = Member::factory()->create(['group_id' => $group->id]);
        $secondMember = Member::factory()->create(['group_id' => $group->id]);
        $firstPlayer = Player::factory()->create(['member_id' => $firstMember->id]);
        $secondPlayer = Player::factory()->create(['member_id' => $secondMember->id]);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        Prediction::factory()->create([
            'player_id' => $firstPlayer->id,
            'game_id' => $game->id,
        ]);

        $result = $service->evaluate($secondPlayer, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 14,
            'away_team_prediction' => 13,
        ]));

        expect($result->isValid())->toBeFalse();
        expect($result->violations)->toHaveCount(1);
        expect($result->violations[0]->key)->toBe((new UniqueGroupPredictionPolicy)->key());
    });

    test('allows update when current prediction is the only group prediction', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This validates self-exclusion behavior for update flows under uniqueness checks.
        $group = Group::factory()->create([
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]);
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);
        $prediction = Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
        ]);

        $result = $service->evaluate($player, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 30,
            'away_team_prediction' => 28,
        ]), $prediction);

        expect($result->isValid())->toBeTrue();
        expect($result->violations)->toBe([]);
    });

    test('adds minimum lead time violation when that policy is enabled and kickoff is too close', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This verifies the second group-level policy branch and expected violation metadata.
        $group = Group::factory()->create([
            'enabled_prediction_policies' => ['minimum-lead-time-before-lock'],
        ]);
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addMinutes(20)->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        $result = $service->evaluate($player, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 31,
            'away_team_prediction' => 27,
        ]));

        expect($result->isValid())->toBeFalse();
        expect($result->violations)->toHaveCount(1);
        expect($result->violations[0]->key)->toBe((new MinimumLeadTimeBeforeLockPolicy)->key());
        expect($result->violations[0]->scope)->toBe(PredictionPolicyScope::GROUP);
        expect($result->summary())->toContain('Minimum lead time before lock');
    });

    test('adds both enabled group violations in group rule order when both fail', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This locks down aggregate behavior for multiple group failures in one submission.
        $group = Group::factory()->create([
            'enabled_prediction_policies' => [
                'group-unique-prediction',
                'minimum-lead-time-before-lock',
            ],
        ]);
        $firstMember = Member::factory()->create(['group_id' => $group->id]);
        $secondMember = Member::factory()->create(['group_id' => $group->id]);
        $firstPlayer = Player::factory()->create(['member_id' => $firstMember->id]);
        $secondPlayer = Player::factory()->create(['member_id' => $secondMember->id]);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addMinutes(20)->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        Prediction::factory()->create([
            'player_id' => $firstPlayer->id,
            'game_id' => $game->id,
        ]);

        $result = $service->evaluate($secondPlayer, ValidatedPredictionData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 33,
            'away_team_prediction' => 29,
        ]));

        expect($result->isValid())->toBeFalse();
        expect($result->violations)->toHaveCount(2);
        expect($result->violations[0]->key)->toBe((new UniqueGroupPredictionPolicy)->key());
        expect($result->violations[1]->key)->toBe((new MinimumLeadTimeBeforeLockPolicy)->key());
        expect($result->violations[0]->scope)->toBe(PredictionPolicyScope::GROUP);
        expect($result->violations[1]->scope)->toBe(PredictionPolicyScope::GROUP);
        expect($result->summary())->toContain('Unique group prediction');
        expect($result->summary())->toContain('Minimum lead time before lock');
    });
});

describe('appRules', function () {
    test('returns app-level policies in evaluation order', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This protects the expected ordering consumed by evaluate().
        $rules = $service->appRules();

        expect($rules)->toHaveCount(2);
        expect($rules[0])->toBeInstanceOf(PredictionLockTimePolicy::class);
        expect($rules[1])->toBeInstanceOf(SeasonActivePolicy::class);
        expect($rules[0]->scope())->toBe(PredictionPolicyScope::APP);
        expect($rules[1]->scope())->toBe(PredictionPolicyScope::APP);
    });
});

describe('groupRules', function () {
    test('returns available group-level policies in evaluation order', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This documents the full optional group policy catalog.
        $rules = $service->groupRules();

        expect($rules)->toHaveCount(2);
        expect($rules[0])->toBeInstanceOf(UniqueGroupPredictionPolicy::class);
        expect($rules[1])->toBeInstanceOf(MinimumLeadTimeBeforeLockPolicy::class);
        expect($rules[0]->scope())->toBe(PredictionPolicyScope::GROUP);
        expect($rules[1]->scope())->toBe(PredictionPolicyScope::GROUP);
    });
});

describe('enabledGroupRules', function () {
    test('returns only rules enabled on the given group', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This verifies key-based filtering against group configuration.
        $group = Group::factory()->create([
            'enabled_prediction_policies' => ['minimum-lead-time-before-lock'],
        ]);

        $rules = $service->enabledGroupRules($group);

        expect($rules)->toHaveCount(1);
        expect($rules[0])->toBeInstanceOf(MinimumLeadTimeBeforeLockPolicy::class);
    });

    test('returns an empty list when no group policies are enabled', function () {
        $service = app(PredictionPolicyEvaluatorService::class);

        // This confirms group-level checks can be fully disabled per group.
        $group = Group::factory()->create([
            'enabled_prediction_policies' => [],
        ]);

        $rules = $service->enabledGroupRules($group);

        expect($rules)->toBe([]);
    });
});
