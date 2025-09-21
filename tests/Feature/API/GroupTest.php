<?php

use App\Models\Follow;
use App\Models\Group;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = actAsAPIUser();
});

test('a group can be created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // there should be no groups in the db
    $this->assertDatabaseCount('groups', 0);

    // post the group data
    $this->post('api/v1/groups', $groupData)->assertCreated();

    // there should be 1 group in the db
    $this->assertDatabaseCount('groups', 1);
});

test('the group is returned when a group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post('api/v1/groups', $groupData)
        ->assertCreated()
        ->assertJson(['data' => [
            'name' => $groupData['name'],
            'owner_id' => $groupData['owner_id'],
            'member_limit' => $groupData['member_limit'],
            'player_limit' => $groupData['player_limit'],
        ],
        ]);
});

test('the ulid field is populated when a group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post('api/v1/groups', $groupData)->assertCreated();

    // get the group we posted
    $group = Group::first();

    expect(Str::isUlid($group->ulid))->toBeTrue();
});

test('the invite_code field is populated when a group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post('api/v1/groups', $groupData)->assertCreated();

    // get the group we posted
    $group = Group::first();

    expect($group->invite_code)->toBeString();
});

test('the member limit field uses the initial value when a group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post('api/v1/groups', $groupData)->assertCreated();

    // get the group we posted
    $group = Group::first();

    expect($group->member_limit)->toBe(Group::INITIAL_MEMBER_LIMIT);
});

test('the player limit field uses the initial value when a group is created', function () {
    // make data for a group
    $groupData = Group::factory()->make()->getAttributes();

    // post the group data
    $this->post('api/v1/groups', $groupData)->assertCreated();

    // get the group we posted
    $group = Group::first();

    expect($group->player_limit)->toBe(Group::INITIAL_PLAYER_LIMIT);
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
            'member_limit' => $group->member_limit,
            'player_limit' => $group->player_limit,
        ],
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
        'group_id' => $group->id,
        'name' => 'updatedName',
        'member_limit' => 40,
        'player_limit' => 4,
    ];

    // post the data
    $this->patch("api/v1/groups/{$group->ulid}", $data)->assertNoContent();

    $group->refresh();

    expect($group->name)->toBe($data['name']);
    expect($group->member_limit)->toBe($data['member_limit']);
    expect($group->player_limit)->toBe($data['player_limit']);
});

test('a lists of groups can be retrieved', function () {
    // create 2 groups
    [$group1, $group2] = Group::factory()->count(2)->create();

    // get the groups
    $this->get('api/v1/groups')
        ->assertOk()
        ->assertJson(['data' => [
            [
                'name' => $group1->name,
                'owner_id' => $group1->owner_id,
                'member_limit' => $group1->member_limit,
                'player_limit' => $group1->player_limit,
            ], [
                'name' => $group2->name,
                'owner_id' => $group2->owner_id,
                'member_limit' => $group2->member_limit,
                'player_limit' => $group2->player_limit,
            ],
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
                'member_limit' => $group1->member_limit,
                'player_limit' => $group1->player_limit,
            ], [
                'name' => $group2->name,
                'owner_id' => $group2->owner_id,
                'member_limit' => $group2->member_limit,
                'player_limit' => $group2->player_limit,
            ],
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
                'member_limit' => $group2->member_limit,
                'player_limit' => $group2->player_limit,
            ],
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

test('a group can follow a team', function () {
    // create a group
    $group = Group::factory()->create();

    // set the team and season to follow
    $data = [
        'team_id' => Team::factory()->create()->id,
        'season_id' => Season::factory()->create()->id,
    ];

    // there should be 0 follow in the db
    $this->assertDatabaseCount('follows', 0);

    // try to follow the team in a season
    $this->post("api/v1/groups/{$group->ulid}/follow", $data)->assertCreated();

    // there should be 1 follow in the db
    $this->assertDatabaseCount('follows', 1);
});

test('following a team populates the uild field', function () {
    // create a group
    $group = Group::factory()->create();

    // set the team and season to follow
    $data = [
        'team_id' => Team::factory()->create()->id,
        'season_id' => Season::factory()->create()->id,
    ];

    // there should be 0 follow in the db
    $this->assertDatabaseCount('follows', 0);

    // try to follow the team in a season
    $this->post("api/v1/groups/{$group->ulid}/follow", $data)->assertCreated();

    // get the follow we posted
    $follow = $group->follow;

    expect(Str::isUlid($follow->ulid))->toBeTrue();
});

test('a team cannot be followed for a season that has ended', function () {
    // create a group
    $group = Group::factory()->create();

    // set the team and season to follow
    $data = [
        'team_id' => Team::factory()->create()->id,
        'season_id' => Season::factory()->create(['season_end' => '2019-12-28'])->id,
    ];

    // try to follow the team in a season
    $this->post("api/v1/groups/{$group->ulid}/follow", $data)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'season_id' => ['Season has ended.'],
        ],
        ]);
});

test('cannot follow a team if already following a team', function () {
    // create a group
    $group = Group::factory()->create();
    // follow a team
    $follow = Follow::factory()->create(['group_id' => $group->id]);

    // set the team and season to follow
    $data = [
        'team_id' => Team::factory()->create()->id,
        'season_id' => Season::factory()->create()->id,
    ];

    // try to follow the team in a season
    $this->post("api/v1/groups/{$group->ulid}/follow", $data)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'follow' => ['This group is already following a team.'],
        ],
        ]);
});

test('a follow can be removed', function () {
    // create a group
    $group = Group::factory()->create();
    // follow a team
    $follow = Follow::factory()->create(['group_id' => $group->id]);

    // there should be 1 follow in the db
    $this->assertDatabaseCount('follows', 1);

    // try to remove the follow
    $this->delete("api/v1/groups/{$group->ulid}/follow/{$follow->ulid}")->assertAccepted();

    // there should be 0 follow in the db
    $this->assertDatabaseCount('follows', 0);
});
