<?php

use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->user = signInRegularUser();
});

describe('create', function () {
    test('shows create form', function () {
        $response = $this->get(route('groups.create'));

        $response->assertOk();
        $response->assertViewIs('groups.create');
    });
});

describe('store', function () {
    test('creates a group', function () {
        $groupData = [
            'name' => 'Test Group',
        ];

        $this->assertDatabaseCount('groups', 0);

        $response = $this->post(route('groups.store'), $groupData);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('groups', 1);
        $this->assertDatabaseHas('groups', [
            'name' => $groupData['name'],
            'owner_id' => $this->user->id,
        ]);
    });

    test('flashes success message with invite code', function () {
        $groupData = [
            'name' => 'Test Group',
        ];

        $this->post(route('groups.store'), $groupData)->assertRedirect();

        expect(session('alert')['message'])->toContain('Group created successfully! Invite code:');
    });
});

describe('join', function () {
    test('shows join form', function () {
        $response = $this->get(route('groups.join'));

        $response->assertOk();
        $response->assertViewIs('groups.join');
    });
});

describe('requestJoin', function () {
    test('joins group with valid invite code', function () {
        $group = Group::factory()->create();

        $this->assertDatabaseCount('members', 1); // owner member

        $response = $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('members', 2);
        $this->assertDatabaseHas('members', [
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::PENDING->value,
        ]);
    });

    test('flashes success message on join', function () {
        $group = Group::factory()->create();

        $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ])->assertRedirect();

        expect(session('alert')['message'])->toBe('Successfully joined the group!');
    });

    test('fails with invalid invite code', function () {
        $response = $this->post(route('groups.request-join'), [
            'invite_code' => 'invalid',
        ]);

        $response->assertRedirect();
        expect(session('alert')['message'])->toBe('Invalid invite code.');
    });

    test('fails with missing invite code', function () {
        $response = $this->post(route('groups.request-join'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors('invite_code');
    });

    test('fails if already a member', function () {
        $group = Group::factory()->create();

        // join once
        $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        // try to join again
        $response = $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        $response->assertRedirect();
        expect(session('alert')['message'])->toBe('You are already a member of this group.');
    });

    test('fails if member limit reached', function () {
        $group = Group::factory()->create();

        // set member limit to 1 (owner is already a member)
        $group->update(['member_limit' => 1]);

        // try to join
        $response = $this->post(route('groups.request-join'), [
            'invite_code' => $group->invite_code,
        ]);

        $response->assertRedirect();
        expect(session('alert')['message'])->toBe('Group member limit reached.');
    });
});

describe('show', function () {
    test('shows group details for member', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $response = $this->get(route('groups.show', $group));

        $response->assertOk();
        $response->assertViewIs('groups.show');
        $response->assertViewHas('group', $group);
    });

    test('denies access to non-members', function () {
        $group = Group::factory()->create();

        $response = $this->get(route('groups.show', $group));

        $response->assertForbidden();
    });

    test('denies access to pending members', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::PENDING->value,
        ]);
        $group = $member->group;

        $response = $this->get(route('groups.show', $group));

        $response->assertForbidden();
    });

    test('shows create player action on group page when current member has no players', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'players']));

        $response->assertOk();
        $response->assertSee(route('groups.members.players.create', ['group' => $group, 'member' => $member]), false);
    });

    test('hides create player action on group page once regular member reaches self-service limit', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        Player::factory()->for($member)->create();

        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'players']));

        $response->assertOk();
        $response->assertDontSee(route('groups.members.players.create', ['group' => $group, 'member' => $member]), false);
    });

    test('hides create player action on group page when member reached player limit', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        Player::factory()->for($member)->create();

        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'players']));

        $response->assertOk();
        $response->assertDontSee(route('groups.members.players.create', ['group' => $group, 'member' => $member]), false);
    });

    test('lists member players alphabetically without query filtering', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        Player::factory()->for($member)->create(['player_name' => 'Alpha Runner']);
        Player::factory()->for($member)->create(['player_name' => 'Beta Shooter']);

        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'players']));

        $response->assertOk();
        $response->assertViewHas('memberPlayers', function ($memberPlayers) {
            return $memberPlayers->count() === 2
                && $memberPlayers->first()->player_name === 'Alpha Runner';
        });
    });

    test('shows sport icon when follow is scoped and all sports icons when unscoped', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => Team::factory()->create()->id,
            'sport' => Sport::FOOTBALL,
        ]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => Team::factory()->create()->id,
            'sport' => null,
        ]);

        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'details']));

        $response->assertOk();
        $response->assertSee('&#127944;', false);
        $response->assertSee('&#127936;', false);
    });

    test('defaults to details tab when tab query is invalid', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->get(route('groups.show', ['group' => $member->group, 'tab' => 'not-a-tab']));

        $response->assertOk();
        $response->assertViewHas('activeTab', 'details');
    });

    test('upcoming games tab only shows followed-team games in follow sport scope', function () {
        // Arrange: approved member with a group that follows one team scoped to football.
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;
        Player::factory()->for($member)->create();

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL, Sport::BASKETBALL])->create([
            'organization' => 'Followed',
            'designation' => 'Team',
            'abbreviation' => 'FLW',
        ]);

        $footballOpponent = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Football',
            'designation' => 'Opponent',
            'abbreviation' => 'FOP',
        ]);

        $basketballOpponent = Team::factory()->withSports([Sport::BASKETBALL])->create([
            'organization' => 'Basketball',
            'designation' => 'Opponent',
            'abbreviation' => 'BOP',
        ]);

        $footballSeason = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);
        $basketballSeason = Season::factory()->active()->create(['sport' => Sport::BASKETBALL->value]);

        $unfollowedFootballTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Unfollowed',
            'designation' => 'Football',
            'abbreviation' => 'UFB',
        ]);

        $unfollowedFootballOpponent = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Other',
            'designation' => 'Opponent',
            'abbreviation' => 'OOP',
        ]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => Sport::FOOTBALL,
        ]);

        Game::factory()->create([
            'season_id' => $footballSeason->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $footballOpponent->id,
            'start_date_time' => now()->addDays(2)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        Game::factory()->create([
            'season_id' => $basketballSeason->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $basketballOpponent->id,
            'start_date_time' => now()->addDays(2)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // This game is upcoming, but should not render because the followed team is not in it.
        Game::factory()->create([
            'season_id' => $footballSeason->id,
            'home_team_id' => $unfollowedFootballTeam->id,
            'away_team_id' => $unfollowedFootballOpponent->id,
            'start_date_time' => now()->addDays(2)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // Act: open the upcoming-games tab.
        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'upcoming-games']));

        // Assert: only the followed-team football matchup is rendered.
        $response->assertOk();
        $response->assertSee('Followed Team (FLW)');
        $response->assertSee('Football Opponent (FOP)');
        $response->assertDontSee('Basketball Opponent (BOP)');
        $response->assertDontSee('Unfollowed Football (UFB)');
        $response->assertDontSee('Other Opponent (OOP)');
        $response->assertSee('cursor-pointer');
    });

    test('upcoming games tab excludes games that are already in the past', function () {
        // Arrange: group follows a team, but the only followed-team game is in the past.
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Past',
            'designation' => 'Check',
            'abbreviation' => 'PST',
        ]);
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Opponent',
            'designation' => 'Check',
            'abbreviation' => 'OPP',
        ]);

        $unfollowedFutureHome = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Future',
            'designation' => 'Hidden',
            'abbreviation' => 'FUT',
        ]);

        $unfollowedFutureAway = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Still',
            'designation' => 'Hidden',
            'abbreviation' => 'STH',
        ]);

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->subDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // This game is upcoming but should not render because no followed team is involved.
        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $unfollowedFutureHome->id,
            'away_team_id' => $unfollowedFutureAway->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // Act: open the upcoming-games tab.
        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'upcoming-games']));

        // Assert: no eligible games are shown.
        $response->assertOk();
        $response->assertSee('No upcoming games');
        $response->assertDontSee('Past Check (PST)');
        $response->assertDontSee('Future Hidden (FUT)');
        $response->assertDontSee('Still Hidden (STH)');
    });

    test('upcoming games tab shows open reason text for active games before lock', function () {
        // Arrange: one eligible open game and one non-eligible upcoming game.
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;
        Player::factory()->for($member)->create();

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Open',
            'designation' => 'Status',
            'abbreviation' => 'OPN',
        ]);
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Reason',
            'designation' => 'Check',
            'abbreviation' => 'RSN',
        ]);

        $unfollowedHome = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Hidden',
            'designation' => 'Open',
            'abbreviation' => 'HOP',
        ]);

        $unfollowedAway = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Hidden',
            'designation' => 'Away',
            'abbreviation' => 'HAY',
        ]);

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addHours(4)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $unfollowedHome->id,
            'away_team_id' => $unfollowedAway->id,
            'start_date_time' => now()->addHours(3)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // Act: open the upcoming-games tab.
        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'upcoming-games']));

        // Assert: open-state reason text is shown only for eligible games.
        $response->assertOk();
        $response->assertSee('Open for prediction');
        $response->assertDontSee('Unavailable');
        $response->assertDontSee('Hidden Open (HOP)');
        $response->assertDontSee('Hidden Away (HAY)');
    });

    test('upcoming games tab shows season inactive reason for inactive-season games', function () {
        // Arrange: eligible game in an inactive season and an unrelated inactive game.
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;
        Player::factory()->for($member)->create();

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Inactive',
            'designation' => 'Followed',
            'abbreviation' => 'IAF',
        ]);
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Inactive',
            'designation' => 'Opponent',
            'abbreviation' => 'IAO',
        ]);

        $unfollowedInactiveHome = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Hidden',
            'designation' => 'Inactive',
            'abbreviation' => 'HIN',
        ]);

        $unfollowedInactiveAway = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Hidden',
            'designation' => 'Opponent',
            'abbreviation' => 'HIO',
        ]);

        $inactiveSeason = Season::factory()->create([
            'sport' => Sport::FOOTBALL->value,
            'active' => false,
        ]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        Game::factory()->create([
            'season_id' => $inactiveSeason->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        Game::factory()->create([
            'season_id' => $inactiveSeason->id,
            'home_team_id' => $unfollowedInactiveHome->id,
            'away_team_id' => $unfollowedInactiveAway->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // Act: open the upcoming-games tab.
        $response = $this->get(route('groups.show', ['group' => $group, 'tab' => 'upcoming-games']));

        // Assert: eligible game shows closed reason; unrelated game remains hidden.
        $response->assertOk();
        $response->assertSee('Season inactive');
        $response->assertSee('Inactive Followed (IAF)');
        $response->assertDontSee('Hidden Inactive (HIN)');
        $response->assertDontSee('Hidden Opponent (HIO)');
    });

});

describe('storePrediction', function () {
    test('approved member can submit prediction for their own player', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $player = Player::factory()->for($member)->create();

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $response = $this->post(route('groups.predictions.store', ['group' => $group, 'player' => $player]), [
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 31,
            'away_team_prediction' => 24,
        ]);

        $response->assertRedirect(route('groups.show', ['group' => $group, 'tab' => 'upcoming-games']));
        $this->assertDatabaseHas('predictions', [
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 31,
            'away_team_prediction' => 24,
        ]);
    });

    test('approved member cannot submit prediction for another members player', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $otherUser->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $otherPlayer = Player::factory()->for($otherMember)->create();

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $response = $this->post(route('groups.predictions.store', ['group' => $group, 'player' => $otherPlayer]), [
            'player_id' => $otherPlayer->id,
            'game_id' => $game->id,
            'home_team_prediction' => 17,
            'away_team_prediction' => 14,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('predictions', [
            'player_id' => $otherPlayer->id,
            'game_id' => $game->id,
        ]);
    });

    test('ajax submit returns json validation errors for invalid prediction payload', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $player = Player::factory()->for($member)->create();

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $response = $this->postJson(route('groups.predictions.store', ['group' => $group, 'player' => $player]), [
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => -1,
            'away_team_prediction' => 7,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['home_team_prediction']);
    });

    test('ajax submit returns json policy violation errors when game is locked', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $player = Player::factory()->for($member)->create();

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->subHour()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $response = $this->postJson(route('groups.predictions.store', ['group' => $group, 'player' => $player]), [
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 21,
            'away_team_prediction' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prediction']);
    });

    test('dashboard-origin submit redirects back to dashboard', function () {
        // Arrange: approved member, own player, eligible followed-team game.
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $player = Player::factory()->for($member)->create();
        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // Act: submit from dashboard flow context.
        $response = $this->post(route('groups.predictions.store', ['group' => $group, 'player' => $player]), [
            'redirect_to' => 'dashboard',
            'dashboard_prediction_context' => $group->ulid.'|'.$game->id,
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 30,
            'away_team_prediction' => 27,
        ]);

        // Assert: successful save returns to dashboard.
        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('predictions', [
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 30,
            'away_team_prediction' => 27,
        ]);
    });

    test('dashboard-origin policy failure redirects back to dashboard with validation errors', function () {
        // Arrange: approved member with locked game to trigger policy violation.
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $player = Player::factory()->for($member)->create();
        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->subHour()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // Act: submit from dashboard flow context.
        $response = $this->post(route('groups.predictions.store', ['group' => $group, 'player' => $player]), [
            'redirect_to' => 'dashboard',
            'dashboard_prediction_context' => $group->ulid.'|'.$game->id,
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 21,
            'away_team_prediction' => 17,
        ]);

        // Assert: redirect stays on dashboard and exposes prediction validation error.
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasErrors('prediction');
        $response->assertSessionHasInput('dashboard_prediction_context', $group->ulid.'|'.$game->id);
    });
});

describe('updatePrediction', function () {
    test('approved member can update an existing prediction for their own player', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;
        $player = Player::factory()->for($member)->create();

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);
        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $prediction = Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 10,
            'away_team_prediction' => 7,
        ]);

        $response = $this->patch(route('groups.predictions.update', ['group' => $group, 'player' => $player, 'prediction' => $prediction]), [
            'prediction_id' => $prediction->id,
            'home_team_prediction' => 27,
            'away_team_prediction' => 20,
        ]);

        $response->assertRedirect(route('groups.show', ['group' => $group, 'tab' => 'upcoming-games']));

        $prediction->refresh();
        expect($prediction->home_team_prediction)->toBe(27);
        expect($prediction->away_team_prediction)->toBe(20);
    });

    test('ajax update returns json success payload', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;
        $player = Player::factory()->for($member)->create();

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);
        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $prediction = Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 14,
            'away_team_prediction' => 10,
        ]);

        $response = $this->patchJson(route('groups.predictions.update', ['group' => $group, 'player' => $player, 'prediction' => $prediction]), [
            'prediction_id' => $prediction->id,
            'home_team_prediction' => 35,
            'away_team_prediction' => 28,
        ]);

        $response->assertOk();
        $response->assertJsonPath('prediction.home_team_prediction', 35);
        $response->assertJsonPath('prediction.away_team_prediction', 28);
    });

    test('ajax update returns json policy violation errors when game is locked', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;
        $player = Player::factory()->for($member)->create();

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);
        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->subHour()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $prediction = Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 20,
            'away_team_prediction' => 17,
        ]);

        $response = $this->patchJson(route('groups.predictions.update', ['group' => $group, 'player' => $player, 'prediction' => $prediction]), [
            'prediction_id' => $prediction->id,
            'home_team_prediction' => 24,
            'away_team_prediction' => 21,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prediction']);
    });

    test('update returns not found when prediction does not belong to routed player', function () {
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;

        $player = Player::factory()->for($member)->create();
        $otherPlayer = Player::factory()->for($member)->create();

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);
        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $prediction = Prediction::factory()->create([
            'player_id' => $otherPlayer->id,
            'game_id' => $game->id,
        ]);

        $response = $this->patch(route('groups.predictions.update', ['group' => $group, 'player' => $player, 'prediction' => $prediction]), [
            'prediction_id' => $prediction->id,
            'home_team_prediction' => 14,
            'away_team_prediction' => 13,
        ]);

        $response->assertNotFound();
    });

    test('dashboard-origin update redirects back to dashboard', function () {
        // Arrange: approved member, existing prediction, and open game.
        $member = Member::factory()->create([
            'user_id' => $this->user->id,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $group = $member->group;
        $player = Player::factory()->for($member)->create();

        $season = Season::factory()->active()->create(['sport' => Sport::FOOTBALL->value]);
        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $prediction = Prediction::factory()->create([
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 17,
            'away_team_prediction' => 10,
        ]);

        // Act: update from dashboard flow context.
        $response = $this->patch(route('groups.predictions.update', ['group' => $group, 'player' => $player, 'prediction' => $prediction]), [
            'redirect_to' => 'dashboard',
            'dashboard_prediction_context' => $group->ulid.'|'.$game->id,
            'prediction_id' => $prediction->id,
            'home_team_prediction' => 24,
            'away_team_prediction' => 13,
        ]);

        // Assert: successful update returns to dashboard and persists new values.
        $response->assertRedirect(route('dashboard'));

        $prediction->refresh();
        expect($prediction->home_team_prediction)->toBe(24);
        expect($prediction->away_team_prediction)->toBe(13);
    });
});

describe('edit', function () {
    test('shows edit form for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->get(route('groups.edit', $group));

        $response->assertOk();
        $response->assertViewIs('groups.edit');
        $response->assertViewHas('group', $group);
    });

    test('shows edit form for admin', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->get(route('groups.edit', $group));

        $response->assertOk();
        $response->assertViewIs('groups.edit');
    });

    test('denies access to regular members', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->get(route('groups.edit', $group));

        $response->assertForbidden();
    });

    test('denies access to pending admins', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->get(route('groups.edit', $group));

        $response->assertForbidden();
    });

    test('selects current admin member by default when no member query is provided', function () {
        $group = Group::factory()->create();
        $adminMember = Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        Player::factory()->for($adminMember)->create(['player_name' => 'Admin Player']);

        $response = $this->get(route('groups.edit', $group));

        $response->assertOk();
        $response->assertViewHas('selectedMember', fn ($selectedMember) => $selectedMember?->id === $adminMember->id);
        $response->assertViewHas('managedPlayers', fn ($managedPlayers) => $managedPlayers && $managedPlayers->count() === 1);
    });

    test('uses member query to select member and list managed players', function () {
        $group = Group::factory()->create();

        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $targetUser = User::factory()->create();

        $targetMember = Member::factory()->create([
            'user_id' => $targetUser->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        Player::factory()->for($targetMember)->create(['player_name' => 'Target Match']);
        Player::factory()->for($targetMember)->create(['player_name' => 'Target Other']);

        $response = $this->get(
            route('groups.edit', $group).'?member='.$targetMember->ulid.'&q=Match'
        );

        $response->assertOk();
        $response->assertViewHas('selectedMember', fn ($selectedMember) => $selectedMember?->id === $targetMember->id);
        $response->assertViewHas('managedPlayers', function ($managedPlayers) {
            return $managedPlayers
                && $managedPlayers->count() === 2
                && $managedPlayers->first()->player_name === 'Target Match';
        });
    });

    test('falls back to current admin member when member query does not match an approved member', function () {
        $group = Group::factory()->create();

        $adminMember = Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        Player::factory()->for($adminMember)->create(['player_name' => 'Fallback Admin Player']);

        $response = $this->get(route('groups.edit', [
            'group' => $group,
            'member' => '01INVALIDULIDVALUE',
        ]));

        $response->assertOk();
        $response->assertViewHas('selectedMember', fn ($selectedMember) => $selectedMember?->id === $adminMember->id);
        $response->assertViewHas('managedPlayers', fn ($managedPlayers) => $managedPlayers && $managedPlayers->count() === 1);
    });
});

describe('update', function () {
    test('updates group for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id, 'name' => 'Old Name']);

        $response = $this->patch(route('groups.update', $group), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $group->refresh();
        expect($group->name)->toBe('New Name');
        expect($group->owner_id)->toBe($this->user->id); // owner should not change
    });

    test('updates group for admin', function () {
        $group = Group::factory()->create(['name' => 'Old Name']);
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->patch(route('groups.update', $group), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $group->refresh();
        expect($group->name)->toBe('New Name');
    });

    test('flashes success message on update', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $this->patch(route('groups.update', $group), ['name' => 'Updated Name'])->assertRedirect();

        expect(session('alert')['message'])->toBe('Group updated successfully!');
    });

    test('rejects empty group name on update', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id, 'name' => 'Original Name']);

        $response = $this->patch(route('groups.update', $group), [
            'name' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('name');

        $group->refresh();
        expect($group->name)->toBe('Original Name');
    });

    test('denies update to pending admins', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->patch(route('groups.update', $group), ['name' => 'New Name']);

        $response->assertForbidden();
    });
});

describe('updatePolicies', function () {
    test('updates group prediction policies for owner', function () {
        $originalName = 'Policy Test Group';
        $originalMemberLimit = 19;
        $originalPlayerLimit = 7;

        $group = Group::factory()->create([
            'owner_id' => $this->user->id,
            'name' => $originalName,
            'member_limit' => $originalMemberLimit,
            'player_limit' => $originalPlayerLimit,
            'enabled_prediction_policies' => [],
        ]);

        $response = $this->patch(route('groups.update-policies', $group), [
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $group->refresh();
        expect($group->enabled_prediction_policies)->toBe(['group-unique-prediction']);
        expect($group->name)->toBe($originalName);
        expect($group->member_limit)->toBe($originalMemberLimit);
        expect($group->player_limit)->toBe($originalPlayerLimit);
        expect($group->owner_id)->toBe($this->user->id);
    });

    test('clears group prediction policies when no checkbox is selected', function () {
        $group = Group::factory()->create([
            'owner_id' => $this->user->id,
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]);

        $response = $this->patch(route('groups.update-policies', $group), []);

        $response->assertRedirect(route('groups.show', $group));
        $group->refresh();
        expect($group->enabled_prediction_policies)->toBe([]);
    });

    test('updates group prediction policies for admin', function () {
        $group = Group::factory()->create([
            'enabled_prediction_policies' => [],
        ]);

        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->patch(route('groups.update-policies', $group), [
            'enabled_prediction_policies' => ['minimum-lead-time-before-lock'],
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $group->refresh();
        expect($group->enabled_prediction_policies)->toBe(['minimum-lead-time-before-lock']);
    });

    test('rejects invalid policy keys', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->patch(route('groups.update-policies', $group), [
            'enabled_prediction_policies' => ['not-a-valid-policy'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('enabled_prediction_policies.0');
    });

    test('denies update policies to regular members', function () {
        $group = Group::factory()->create();

        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->patch(route('groups.update-policies', $group), [
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ]);

        $response->assertForbidden();
    });

    test('flashes success message on policy update', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $this->patch(route('groups.update-policies', $group), [
            'enabled_prediction_policies' => ['group-unique-prediction'],
        ])->assertRedirect();

        expect(session('alert')['message'])->toBe('Prediction policies updated successfully!');
    });
});

describe('approveMember', function () {
    test('approves pending member for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertRedirect();
        $pendingMember->refresh();
        expect($pendingMember->status)->toBe(MemberStatus::APPROVED->value);
    });

    test('approves pending member for admin', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertRedirect();
        $pendingMember->refresh();
        expect($pendingMember->status)->toBe(MemberStatus::APPROVED->value);
    });

    test('denies approval to regular members', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('denies approval to non-members', function () {
        $group = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('flashes success message on approval', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->post(route('groups.approve-member', [$group, $pendingMember]))->assertRedirect();

        expect(session('alert')['message'])->toBe('Member approved successfully!');
    });

    test('returns 404 when trying to approve non-pending member', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $approvedMember]));

        $response->assertNotFound();
    });

    test('returns 404 when member does not belong to group', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $otherGroup = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $otherGroup->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.approve-member', [$group, $pendingMember]));

        $response->assertNotFound();
        $pendingMember->refresh();
        expect($pendingMember->status)->toBe(MemberStatus::PENDING->value);
    });
});

describe('rejectMember', function () {
    test('rejects pending member for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $pendingMember->id]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $pendingMember->id]);
    });

    test('rejects pending member for admin', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $pendingMember->id]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $pendingMember->id]);
    });

    test('denies rejection to regular members', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('denies rejection to non-members', function () {
        $group = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('flashes success message on rejection', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $this->post(route('groups.reject-member', [$group, $pendingMember]))->assertRedirect();

        expect(session('alert')['message'])->toBe('Join request rejected.');
    });

    test('denies rejection to pending admins', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::PENDING->value,
        ]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertForbidden();
    });

    test('returns 404 when trying to reject non-pending member', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $approvedMember]));

        $response->assertNotFound();
    });

    test('returns 404 when rejecting member from a different group', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $otherGroup = Group::factory()->create();
        $pendingMember = Member::factory()->create([
            'group_id' => $otherGroup->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->post(route('groups.reject-member', [$group, $pendingMember]));

        $response->assertNotFound();
        $this->assertDatabaseHas('members', ['id' => $pendingMember->id]);
    });
});

describe('removeMember', function () {
    test('removes approved member for owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $approvedMember->id]);

        $response = $this->delete(route('groups.remove-member', [$group, $approvedMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $approvedMember->id]);
    });

    test('removes approved member for admin', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $approvedMember->id]);

        $response = $this->delete(route('groups.remove-member', [$group, $approvedMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $approvedMember->id]);
    });

    test('denies removal to regular members', function () {
        $group = Group::factory()->create();
        Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
        ]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->delete(route('groups.remove-member', [$group, $approvedMember]));

        $response->assertForbidden();
    });

    test('denies removal of owner', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $ownerMember = $group->members()->where('user_id', $group->owner_id)->first();

        $response = $this->delete(route('groups.remove-member', [$group, $ownerMember]));

        $response->assertForbidden();
    });

    test('allows removal of admin if not owner', function () {
        $group = Group::factory()->create();
        $adminMember = Member::factory()->create([
            'user_id' => $this->user->id,
            'group_id' => $group->id,
            'role' => GroupRole::GROUP_ADMIN->value,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->assertDatabaseHas('members', ['id' => $adminMember->id]);

        $response = $this->delete(route('groups.remove-member', [$group, $adminMember]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $adminMember->id]);
    });

    test('flashes success message on removal', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $approvedMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $this->delete(route('groups.remove-member', [$group, $approvedMember]))->assertRedirect();

        expect(session('alert')['message'])->toBe('Member removed from group.');
    });

    test('returns 404 when trying to remove non-approved member', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $pendingMember = Member::factory()->create([
            'group_id' => $group->id,
            'status' => MemberStatus::PENDING->value,
        ]);

        $response = $this->delete(route('groups.remove-member', [$group, $pendingMember]));

        $response->assertNotFound();
    });

    test('returns 404 when removing member from a different group', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $otherGroup = Group::factory()->create();
        $approvedMember = Member::factory()->create([
            'group_id' => $otherGroup->id,
            'status' => MemberStatus::APPROVED->value,
        ]);

        $response = $this->delete(route('groups.remove-member', [$group, $approvedMember]));

        $response->assertNotFound();
        $this->assertDatabaseHas('members', ['id' => $approvedMember->id]);
    });
});

describe('createFollowTeam', function () {
    test('shows follow team form for admin', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $team = Team::factory()->create();

        $response = $this->get(route('groups.follow-team.create', $group));

        $response->assertOk();
        $response->assertViewIs('groups.follow-team');
        $response->assertViewHas(['group', 'teams']);
    });

    test('denies access to non-admin', function () {
        $group = Group::factory()->create();
        $member = Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => GroupRole::GROUP_MEMBER->value,
        ]);

        $response = $this->get(route('groups.follow-team.create', $group));

        $response->assertForbidden();
    });
});

describe('followTeam', function () {
    test('follows team successfully', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id, 'follow_limit' => 2]);
        $team = Team::factory()->create();

        $this->assertDatabaseMissing('follows', ['group_id' => $group->id]);

        $response = $this->post(route('groups.follow-team', $group), [
            'team_id' => $team->id,
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $team->id,
            'sport' => null,
        ]);
        expect(session('alert')['message'])->toBe('Team followed successfully!');
    });

    test('follows team with a sport scope', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id, 'follow_limit' => 2]);
        $team = Team::factory()->withSports([Sport::FOOTBALL])->create();

        $response = $this->post(route('groups.follow-team', $group), [
            'team_id' => $team->id,
            'sport' => Sport::FOOTBALL->value,
        ]);

        $response->assertRedirect(route('groups.show', $group));
        $this->assertDatabaseHas('follows', [
            'group_id' => $group->id,
            'team_id' => $team->id,
            'sport' => Sport::FOOTBALL->value,
        ]);
    });

    test('allows following multiple teams up to follow limit', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id, 'follow_limit' => 2]);
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->create();

        $this->post(route('groups.follow-team', $group), ['team_id' => $firstTeam->id])
            ->assertRedirect(route('groups.show', $group));

        $this->post(route('groups.follow-team', $group), ['team_id' => $secondTeam->id])
            ->assertRedirect(route('groups.show', $group));

        $this->assertDatabaseCount('follows', 2);
    });

    test('rejects follow when group reached follow limit', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id, 'follow_limit' => 1]);
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->create();

        $this->post(route('groups.follow-team', $group), ['team_id' => $firstTeam->id])
            ->assertRedirect(route('groups.show', $group));

        $response = $this->from(route('groups.follow-team.create', $group))
            ->post(route('groups.follow-team', $group), ['team_id' => $secondTeam->id]);

        $response->assertRedirect(route('groups.follow-team.create', $group));
        expect(session('alert')['message'])->toBe('This group has reached its follow limit.');
    });

    test('rejects follow when selected sport is not available for team', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $team = Team::factory()->withSports([Sport::FOOTBALL])->create();

        $response = $this->from(route('groups.follow-team.create', $group))
            ->post(route('groups.follow-team', $group), [
                'team_id' => $team->id,
                'sport' => Sport::BASKETBALL->value,
            ]);

        $response->assertRedirect(route('groups.follow-team.create', $group));
        $response->assertSessionHasErrors('sport');
    });
});

describe('removeFollow', function () {
    test('removes follow successfully', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id, 'follow_limit' => 2]);
        $team = Team::factory()->create();
        $follow = Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $team->id,
        ]);
        $remainingFollow = Follow::factory()->create([
            'group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('follows', ['id' => $follow->id]);

        $response = $this->delete(route('groups.follow.destroy', [$group, $follow]));

        $response->assertRedirect(route('groups.show', $group));
        $this->assertDatabaseMissing('follows', ['id' => $follow->id]);
        $this->assertDatabaseHas('follows', ['id' => $remainingFollow->id]);
        expect(session('alert')['message'])->toBe('Follow removed successfully!');
    });

    test('returns 404 when follow does not belong to group', function () {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $groupFollow = Follow::factory()->create(['group_id' => $group->id]);

        $otherGroup = Group::factory()->create();
        $otherFollow = Follow::factory()->create(['group_id' => $otherGroup->id]);

        $response = $this->delete(route('groups.follow.destroy', [$group, $otherFollow]));

        $response->assertNotFound();
        $this->assertDatabaseHas('follows', ['id' => $groupFollow->id]);
        $this->assertDatabaseHas('follows', ['id' => $otherFollow->id]);
    });
});
