<?php


use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function() {
    $this->user = signInRegularUser();
});

test('a group can be created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // there should be no groups in the db
    $this->assertDatabaseCount('groups', 0);

    // post the group data
    $this->post("api/v1/groups", $groupData)->assertCreated();

    // there should be 1 group in the db
    $this->assertDatabaseCount('groups', 1);
});

test('the group is returned when a group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post("api/v1/groups", $groupData)
        ->assertCreated()
        ->assertJson(['data' => [
                'name' => $groupData['name'],
                'owner_id' => $groupData['owner_id'],
            ]
        ]);
});

test('the ulid field is populated when a group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post("api/v1/groups", $groupData)->assertCreated();

    // get the group we posted
    $group = Group::first();

    expect(Str::isUlid($group->ulid))->toBeTrue();
});

test('the invite_code field is populated when a group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post("api/v1/groups", $groupData)->assertCreated();

    // get the group we posted
    $group = Group::first();

    expect($group->invite_code)->toBeString();
});

test('a group can be viewed by ulid', function () {
    // create a group
    $group = Group::factory()->create();

    // get the group
    $this->get("api/v1/groups/{$group->ulid}")
        ->assertOk()
        ->assertJson(['data' => [
                'name' => $group->name,
                'owner_id' => $group->owner_id,
            ]
        ]);
});

test('a group cannot be viewed by id', function () {
    // we want to catch the exception not see the pretty response
    $this->withoutExceptionHandling();

    // create a group
    $group = Group::factory()->create();

    // get the group
    $this->get("api/v1/groups/{$group->id}");
})->throws(ModelNotFoundException::class);

test('a group can be updated', function () {
    // create a group
    $group = Group::factory()->create();

    // set fields to update
    $data = [
        'name' => 'updatedName',
    ];

    // post the data
    $this->patch("api/v1/groups/{$group->ulid}", $data)->assertNoContent();

    $group->refresh();
    
    expect($group->name)->toBe($data['name']);
});

test('a lists of groups can be retrieved', function () {
    // create 2 groups
    [$group1, $group2] = Group::factory()->count(2)->create();

    // get the groups
    $this->get("api/v1/groups")
        ->assertOk()
        ->assertJson(['data' => [
            [
                'name' => $group1->name,
                'owner_id' => $group1->owner_id,
            ], [
                'name' => $group2->name,
                'owner_id' => $group2->owner_id,
            ]
        ]]);
});

test('a lists of groups can be filtered by user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // create 2 User1 groups
    [$group1, $group2] = Group::factory()->count(2)->create(['owner_id' => $user1->id]);

    // create 2 User2 groups
    [$group3, $group4] = Group::factory()->count(2)->create(['owner_id' => $user2->id]);

    // get the User1 groups only
    $this->get("api/v1/groups?owner_id={$user1->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJson(['data' => [
            [
               'name' => $group1->name,
               'owner_id' => $group1->owner_id,
           ], [
               'name' => $group2->name,
               'owner_id' => $group2->owner_id,
           ]
        ]]);
});

test('a lists of groups can be filtered by invite_code', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // create 2 User1 groups
    [$group1, $group2] = Group::factory()->count(2)->create(['owner_id' => $user1->id]);

    // create 2 User2 groups
    [$group3, $group4] = Group::factory()->count(2)->create(['owner_id' => $user2->id]);

    // get the User1 groups only
    $this->get("api/v1/groups?invite_code={$group2->invite_code}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [
            [
               'name' => $group2->name,
               'owner_id' => $group2->owner_id,
           ]
        ]]);
});

test('a group can be deleted', function () {
    // create a group
    $group = Group::factory()->create();

    // there should be 1 group in the db
    $this->assertDatabaseCount('groups', 1);

    // delete the group
    $this->delete("api/v1/groups/{$group->ulid}")->assertAccepted();

    // there should be no groups in the db
    $this->assertDatabaseCount('groups', 0);
});