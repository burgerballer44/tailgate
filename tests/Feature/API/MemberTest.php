<?php

use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = actAsAPIUser();
});

test('the group owner is added as a member when the group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // there should be no members in the db
    $this->assertDatabaseCount('members', 0);

    // post the group data
    $this->post('api/v1/groups', $groupData)->assertCreated();

    // there should be 1 member in the db
    $this->assertDatabaseCount('members', 1);

    // get the member
    $member = Member::first();

    expect($member->user_id)->toBe($groupData['owner_id']);
});

test('the group owner is a group admin when the group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post('api/v1/groups', $groupData)->assertCreated();

    // get the member
    $member = Member::first();

    expect($member->role)->toBe(GroupRole::GROUP_ADMIN->value);
});

test('a user can be added to a group as a member', function () {
    // create a group
    $group = Group::factory()->create();
    // create a user to add as a member
    $user = User::factory()->create();

    $memberData = [
        'user_id' => $user->id,
    ];

    // there should be 1 members in the db
    $this->assertDatabaseCount('members', 1);

    // try to add the member
    $this->post("api/v1/groups/{$group->ulid}/members", $memberData)->assertCreated();

    // there should be 2 members in the db
    $this->assertDatabaseCount('members', 2);
});

test('the ulid field is populated when a member is created', function () {
    // create a group
    $group = Group::factory()->create();
    // create a user to add as a member
    $user = User::factory()->create();

    $memberData = [
        'user_id' => $user->id,
    ];

    // try to add the member
    $this->post("api/v1/groups/{$group->ulid}/members", $memberData)->assertCreated();

    // get the member
    $member = $group->members()->first();

    expect(Str::isUlid($member->ulid))->toBeTrue();
});

test('a user cannot be added to a group when the group limit is reached', function () {
    // create a group
    $group = Group::factory()->create();
    // set member limit to 2
    $group->member_limit = 2;
    $group->save();
    // add another member to reach the limit
    $member = Member::factory()->create(['group_id' => $group->id]);
    // create a user to add as the second member
    $user = User::factory()->create();

    $memberData = [
        'user_id' => $user->id,
    ];

    // try to add the member
    $this->post("api/v1/groups/{$group->ulid}/members", $memberData)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'user_id' => ['Group member limit reached.'],
        ],
        ]);
});

test('a member has a group member role when added to a group', function () {
    // create a group
    $group = Group::factory()->create();
    // create a user to add as a member
    $user = User::factory()->create();

    $memberData = [
        'user_id' => $user->id,
    ];

    // try to add the member
    $this->post("api/v1/groups/{$group->ulid}/members", $memberData)->assertCreated();

    // get the member
    $member = $group->members->last();

    expect($member->role)->toBe(GroupRole::GROUP_MEMBER->value);
});

test('a user cannot be added to a group if already a member ', function () {
    // create a group
    $group = Group::factory()->create();

    // use the group owner id
    $memberData = [
        'user_id' => $group->owner->id,
    ];

    // try to add the member
    $this->post("api/v1/groups/{$group->ulid}/members", $memberData)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'user_id' => ['The user is already a member of the group.'],
        ],
        ]);
});

test('the group owner can be changed', function () {
    // create a group
    $group = Group::factory()->create();
    $member = Member::factory()->create(['group_id' => $group->id]);

    // set fields to update
    $data = [
        'owner_id' => $member->user_id,
    ];

    // post the data
    $this->patch("api/v1/groups/{$group->ulid}", $data)->assertNoContent();

    $group->refresh();

    expect($group->owner_id)->toBe($data['owner_id']);
});

test('a group owner cannot be changed if the added user is not a member of the group', function () {
    // create a group
    $group = Group::factory()->create();
    // create a user to set as different owner
    $user = User::factory()->create();

    // set fields to update
    $data = [
        'owner_id' => $user->id,
    ];

    // post the data
    $this->patch("api/v1/groups/{$group->ulid}", $data)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'owner_id' => ['The user is not a member of the group.'],
        ],
        ]);
});

test('a member can be updated', function () {
    // create a group
    $group = Group::factory()->create();
    // add a member
    $member = Member::factory()->create([
        'group_id' => $group->id,
        'role' => GroupRole::GROUP_ADMIN->value,
    ]);

    // set fields to update
    $data = [
        'role' => GroupRole::GROUP_MEMBER->value,
    ];

    // try to update the member
    $this->patch("api/v1/groups/{$group->ulid}/members/{$member->ulid}", $data)->assertNoContent();

    $member->refresh();

    expect($member->role)->toBe($data['role']);
});

test('a members role cannot be updated if they are the last group admin', function () {
    // create a group
    $group = Group::factory()->create();
    // add a group member
    $member = Member::factory()->create([
        'group_id' => $group->id,
        'role' => GroupRole::GROUP_MEMBER->value,
    ]);

    // set fields to update
    $data = [
        'role' => GroupRole::GROUP_MEMBER->value,
    ];

    // try to update the OWNER to be a regular group member
    $this->patch("api/v1/groups/{$group->ulid}/members/{$group->ownerMember->ulid}", $data)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'role' => ['Group admin minimum reached. Please update a different member to the Group Admin role before updating this member.'],
        ],
        ]);

    $member->refresh();

    expect($group->owner->role)->not->toBe($data['role']);
});

test('a member can be removed from the group', function () {
    // create a group
    $group = Group::factory()->create();
    // add a member
    $member = Member::factory()->create(['group_id' => $group->id]);

    // there should be 2 members in the db
    $this->assertDatabaseCount('members', 2);

    // try to remove the member
    $this->delete("api/v1/groups/{$group->ulid}/members/{$member->ulid}")->assertAccepted();

    // there should be 1 member in the db
    $this->assertDatabaseCount('members', 1);
});

test('a member cannot be removed from the group if they are the last admin', function () {
    // create a group
    $group = Group::factory()->create();

    // there should be 1 member in the db
    $this->assertDatabaseCount('members', 1);

    // try to remove the member
    $this->delete("api/v1/groups/{$group->ulid}/members/{$group->ownerMember->ulid}")
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'member_id' => ['Group admin minimum reached. Please update a different member to the Group Admin role before removing this member.'],
        ],
        ]);

    // there should be 1 member in the db
    $this->assertDatabaseCount('members', 1);
});

test('a player can be added', function () {
    // create a group
    $group = Group::factory()->create();
    // add a member
    $member = Member::factory()->create(['group_id' => $group->id]);

    $data = [
        'player_name' => 'some player name',
    ];

    // there should be 0 player in the db
    $this->assertDatabaseCount('players', 0);

    // try to add the player
    $this->post("api/v1/groups/{$group->ulid}/members/{$member->ulid}/player", $data)->assertCreated();

    // there should be 1 member in the db
    $this->assertDatabaseCount('players', 1);
});

test('the ulid field is populated when a player is created', function () {
    // create a group
    $group = Group::factory()->create();
    // add a member
    $member = Member::factory()->create(['group_id' => $group->id]);

    $data = [
        'player_name' => 'some player name',
    ];

    // try to add the player
    $this->post("api/v1/groups/{$group->ulid}/members/{$member->ulid}/player", $data)->assertCreated();

    // get the player
    $player = $member->players()->first();

    expect(Str::isUlid($player->ulid))->toBeTrue();
});

test('a player cannot be added if the limit has been reached', function () {
    // create a group
    $group = Group::factory()->create();
    // set player limit to 1
    $group->player_limit = 1;
    $group->save();
    // add a member
    $member = Member::factory()->create(['group_id' => $group->id]);
    // add a player so the limit is reached
    $player = Player::factory()->create(['member_id' => $member->id]);

    $data = [
        'player_name' => 'some player name',
    ];

    // try to add the player
    $this->post("api/v1/groups/{$group->ulid}/members/{$member->ulid}/player", $data)->assertUnprocessable()
        ->assertJson(['data' => [
            'player_name' => ['Player limit reached.'],
        ],
        ]);
});

test('a player cannot be added if the name has been used by any member in the group', function () {
    // create a group
    $group = Group::factory()->create();
    // add a player to the owner
    $player = Player::factory()->create(['player_name' => 'name used', 'member_id' => $group->ownerMember->id]);
    // add a member
    $member = Member::factory()->create(['group_id' => $group->id]);

    $data = [
        'player_name' => 'name used',
    ];

    // try to add the player name to the member
    $this->post("api/v1/groups/{$group->ulid}/members/{$member->ulid}/player", $data)->assertUnprocessable()
        ->assertJson(['data' => [
            'player_name' => ['Please choose a unique username for this group. This username is unavailable.'],
        ],
        ]);
});

test('the owner of a player can be changed to a different member', function () {
    // create a group
    $group = Group::factory()->create();
    // add a player to the owner
    $player = Player::factory()->create(['member_id' => $group->ownerMember->id]);
    // add a member
    $member = Member::factory()->create(['group_id' => $group->id]);

    $data = [
        'player_id' => $player->id,
        'member_id' => $member->id,
    ];

    // should start off as the owner player
    expect($player->member_id)->toBe($group->ownerMember->id);

    // try to update the player
    $this->patch("api/v1/groups/{$group->ulid}/members/{$group->ownerMember->ulid}/player/{$player->ulid}", $data)->assertNoContent();

    $player->refresh();

    // the player should belong to the other member now
    expect($player->member_id)->toBe($member->id);
});

test('a player can be removed', function () {
    // create a group
    $group = Group::factory()->create();
    // add a player to the owner
    $player = Player::factory()->create(['member_id' => $group->ownerMember->id]);

    // there should be 1 player in the db
    $this->assertDatabaseCount('players', 1);

    // try to delete the player
    $this->delete("api/v1/groups/{$group->ulid}/members/{$group->ownerMember->ulid}/player/{$player->ulid}")->assertAccepted();

    // there should be 0 player in the db
    $this->assertDatabaseCount('players', 0);
});
