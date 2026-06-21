<?php

use App\DTO\PredictionPolicyEvaluationResult;
use App\DTO\PredictionPolicyViolation;
use App\Exceptions\PredictionPolicyViolationException;
use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\PredictionPolicyScope;
use App\Models\Season;
use App\Services\Contracts\PlayerCommandInterface;

beforeEach(function () {
    $this->user = signInDeveloperUser();
});

describe('index', function () {
    test('works', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // visit the index page
        $response = $this->get(route('developer.groups.members.players.index', [$group, $member]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.players.index');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('member', $member);
        $response->assertViewHas('players');
    });
});

describe('create', function () {
    test('shows create form', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        // visit the create page
        $response = $this->get(route('developer.groups.members.players.create', [$group, $member]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.players.create');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('member', $member);
    });
});

describe('store', function () {
    test('works', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        $playerData = [
            'player_name' => 'Test Player',
        ];

        // there should be 0 players in the db
        $this->assertDatabaseCount('players', 0);

        // post the player data
        $response = $this->post(route('developer.groups.members.players.store', [$group, $member]), $playerData);

        // should redirect to index
        $response->assertRedirect(route('developer.groups.members.players.index', [$group, $member]));

        // there should be 1 player in the db
        $this->assertDatabaseCount('players', 1);

        // verify player was created
        $this->assertDatabaseHas('players', [
            'player_name' => $playerData['player_name'],
            'member_id' => $member->id,
        ]);
    });

    test('flashes success message on store', function () {
        // create a group and member
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);

        $playerData = [
            'player_name' => 'Test Player',
        ];

        // post the player data
        $this->post(route('developer.groups.members.players.store', [$group, $member]), $playerData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Player added successfully!');
    });

    test('allows creating a player for pending members in developer admin', function () {
        $group = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING,
        ]);

        $response = $this->post(route('developer.groups.members.players.store', [$group, $pendingMember]), [
            'player_name' => 'Pending Member Player',
        ]);

        $response->assertRedirect(route('developer.groups.members.players.index', [$group, $pendingMember]));

        $this->assertDatabaseHas('players', [
            'member_id' => $pendingMember->id,
            'player_name' => 'Pending Member Player',
        ]);
    });
});

describe('show', function () {
    test('works', function () {
        // create a group, member, and player
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        // visit the show page
        $response = $this->get(route('developer.groups.members.players.show', [$group, $member, $player]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.players.show');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('member', $member);
        $response->assertViewHas('player', $player);
    });
});

describe('edit', function () {
    test('shows edit form', function () {
        // create a group, member, and player
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        // visit the edit page
        $response = $this->get(route('developer.groups.members.players.edit', [$group, $member, $player]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.players.edit');

        // assert data is passed to view
        $response->assertViewHas('group', $group);
        $response->assertViewHas('member', $member);
        $response->assertViewHas('player', $player);
    });
});

describe('update', function () {
    test('updates a player', function () {
        // create a group, member, and player
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id, 'player_name' => 'Original Name']);

        // update data
        $updateData = [
            'player_name' => 'Updated Name',
        ];

        // patch the player data
        $response = $this->patch(route('developer.groups.members.players.update', [$group, $member, $player]), $updateData);

        // should redirect to index
        $response->assertRedirect(route('developer.groups.members.players.index', [$group, $member]));

        // verify player was updated
        $player->refresh();
        expect($player->player_name)->toBe($updateData['player_name']);
    });

    test('flashes success message on update', function () {
        // create a group, member, and player
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        // update data
        $updateData = [
            'player_name' => 'Updated Name',
        ];

        // patch the player data
        $this->patch(route('developer.groups.members.players.update', [$group, $member, $player]), $updateData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Player updated successfully!');
    });
});

describe('destroy', function () {
    test('works', function () {
        // create a group, member, and player
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        // there should be 1 player in the db
        $this->assertDatabaseCount('players', 1);

        // delete the player
        $response = $this->delete(route('developer.groups.members.players.destroy', [$group, $member, $player]));

        // should redirect to index
        $response->assertRedirect(route('developer.groups.members.players.index', [$group, $member]));

        // there should be 0 players in the db
        $this->assertDatabaseCount('players', 0);

        // verify player was deleted
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    });

    test('flashes success message on delete', function () {
        // create a group, member, and player
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        // delete the player
        $this->delete(route('developer.groups.members.players.destroy', [$group, $member, $player]))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Player removed successfully!');
    });
});

describe('submitPrediction', function () {
    test('submit prediction shows policy violation message and redirects back with input', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addHour()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $game->home_team_id,
            'sport' => null,
        ]);

        $result = new PredictionPolicyEvaluationResult([
            new PredictionPolicyViolation(
                key: 'group-unique-prediction',
                label: 'Unique group prediction',
                description: 'When enabled for a group, only one prediction for a game may exist within that group.',
                scope: PredictionPolicyScope::GROUP,
            ),
        ]);

        $this->mock(PlayerCommandInterface::class, function ($mock) use ($player, $result): void {
            $mock->shouldReceive('submitPrediction')
                ->once()
                ->withArgs(function ($boundPlayer, $dto) use ($player): bool {
                    return $boundPlayer instanceof Player
                        && $boundPlayer->id === $player->id
                        && $dto instanceof \App\DTO\ValidatedPredictionData;
                })
                ->andThrow(new PredictionPolicyViolationException($result));
        });

        $response = $this->from(route('developer.groups.members.players.submit-prediction.create', [$group, $member, $player]))
            ->post(route('developer.groups.members.players.submit-prediction', [$group, $member, $player]), [
                'player_id' => $player->id,
                'game_id' => $game->id,
                'home_team_prediction' => 17,
                'away_team_prediction' => 14,
            ]);

        $response->assertRedirect(route('developer.groups.members.players.submit-prediction.create', [$group, $member, $player]));
        expect(session('alert')['type'])->toBe('error');
        expect(session('alert')['message'])->toContain('Prediction submission violates the following policies');
        expect(session('_old_input.game_id'))->toBe($game->id);
    });
});

describe('updatePrediction', function () {
    test('update prediction shows policy violation message and redirects back with input', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create(['group_id' => $group->id]);
        $player = Player::factory()->create(['member_id' => $member->id]);

        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addHour()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        $prediction = Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
        ]);

        $result = new PredictionPolicyEvaluationResult([
            new PredictionPolicyViolation(
                key: 'prediction-lock-time',
                label: 'Prediction lock time',
                description: 'Predictions cannot be submitted or updated after the scheduled game start time.',
                scope: PredictionPolicyScope::APP,
            ),
        ]);

        $this->mock(PlayerCommandInterface::class, function ($mock) use ($prediction, $result): void {
            $mock->shouldReceive('updatePrediction')
                ->once()
                ->withArgs(function ($boundPrediction, $dto) use ($prediction): bool {
                    return $boundPrediction instanceof Prediction
                        && $boundPrediction->id === $prediction->id
                        && $dto instanceof \App\DTO\ValidatedPredictionData;
                })
                ->andThrow(new PredictionPolicyViolationException($result));
        });

        $response = $this->from(route('developer.groups.members.players.predictions.edit', [$group, $member, $player, $prediction]))
            ->patch(route('developer.groups.members.players.predictions.update', [$group, $member, $player, $prediction]), [
                'prediction_id' => $prediction->id,
                'home_team_prediction' => 28,
                'away_team_prediction' => 24,
            ]);

        $response->assertRedirect(route('developer.groups.members.players.predictions.edit', [$group, $member, $player, $prediction]));
        expect(session('alert')['type'])->toBe('error');
        expect(session('alert')['message'])->toContain('Prediction submission violates the following policies');
        expect(session('_old_input.home_team_prediction'))->toBe(28);
    });
});
