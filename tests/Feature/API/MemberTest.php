<?php


use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function() {
    $this->user = signInRegularUser();
});

test('the group owner is added as a member when the group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // there should be no members in the db
    $this->assertDatabaseCount('members', 0);

    // post the group data
    $this->post("api/v1/groups", $groupData)->assertCreated();

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
    $this->post("api/v1/groups", $groupData)->assertCreated();

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
        'group_id' => $group->id,
        'user_id' => $user->id
    ];

    // there should be 1 members in the db
    $this->assertDatabaseCount('members', 1);

    // try to add the member
    $this->post("api/v1/groups/{$group->ulid}/members", $memberData)->assertCreated();

    // there should be 2 members in the db
    $this->assertDatabaseCount('members', 2);
});

test('a member has a group member role when added to a group', function () {
    // create a group
    $group = Group::factory()->create();
    // create a user to add as a member
    $user = User::factory()->create();

    $memberData = [
        'group_id' => $group->id,
        'user_id' => $user->id
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
        'group_id' => $group->id,
        'user_id' => $group->owner->id
    ];

    // try to add the member
    $this->post("api/v1/groups/{$group->ulid}/members", $memberData)->assertUnprocessable()
        ->assertJson(['data' => [
                'user_id' => ['The user is already a member of the group.'],
            ]
        ]);
});

test('a group owner can be updated', function () {
    // create a group
    $group = Group::factory()->create();
    $member = Member::factory()->create(['group_id' => $group->id]);

    // set fields to update
    $data = [
        'group_id' => $group->id,
        'owner_id' => $member->user_id,
    ];

    // post the data
    $this->patch("api/v1/groups/{$group->ulid}", $data)->assertNoContent();

    $group->refresh();
    
    expect($group->owner_id)->toBe($data['owner_id']);
});

test('a group owner cannot be updated if the added user is not a member of the group', function () {
    // create a group
    $group = Group::factory()->create();
    // create a user to set as different owner
    $user = User::factory()->create();

    // set fields to update
    $data = [
        'group_id' => $group->id,
        'owner_id' => $user->id,
    ];

    // post the data
    $this->patch("api/v1/groups/{$group->ulid}", $data)->assertUnprocessable()
        ->assertJson(['data' => [
                'owner_id' => ['The user is not a member of the group.'],
            ]
        ]);
});