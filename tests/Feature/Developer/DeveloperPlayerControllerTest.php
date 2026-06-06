<?php

use App\Models\Group;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Player;

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

describe('creating a player', function () {
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

    test('allows creating players beyond user-facing player limits in developer admin', function () {
        $group = Group::factory()->create(['player_limit' => 1]);
        $member = Member::factory()->create(['group_id' => $group->id]);

        Player::factory()->create([
            'member_id' => $member->id,
            'player_name' => 'Existing Player',
        ]);

        $response = $this->post(route('developer.groups.members.players.store', [$group, $member]), [
            'player_name' => 'Second Player',
        ]);

        $response->assertRedirect(route('developer.groups.members.players.index', [$group, $member]));

        $this->assertDatabaseHas('players', [
            'member_id' => $member->id,
            'player_name' => 'Second Player',
        ]);
    });

    test('allows duplicate player names within the same group in developer admin', function () {
        $group = Group::factory()->create();
        $memberA = Member::factory()->create(['group_id' => $group->id]);
        $memberB = Member::factory()->create(['group_id' => $group->id]);

        Player::factory()->create([
            'member_id' => $memberA->id,
            'player_name' => 'Duplicate Name',
        ]);

        $response = $this->post(route('developer.groups.members.players.store', [$group, $memberB]), [
            'player_name' => 'Duplicate Name',
        ]);

        $response->assertRedirect(route('developer.groups.members.players.index', [$group, $memberB]));

        $this->assertDatabaseCount('players', 2);
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

describe('viewing a player', function () {
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

describe('updating player', function () {
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

describe('deleting a player', function () {
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
