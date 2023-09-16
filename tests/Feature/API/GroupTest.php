<?php


use App\Models\Group;
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

// test('the group is returned when a group is created', function () {
//     // make data for a group
//     $groupData = Group::factory()->make()->getAttributes();

//     // post the group data
//     $this->post("api/v1/groups", $groupData)
//         ->assertCreated()
//         ->assertJson(['data' => [
//             'designation' => $groupData['designation'],
//             'mascot'      => $groupData['mascot'],
//             'sport'       => $groupData['sport'],
//             ]
//         ]);
// });

// test('the ulid field is populated when a team is created', function () {
//     // make data for a group
//     $groupData = Group::factory()->make()->getAttributes();

//     // post the group data
//     $this->post("api/v1/groups", $groupData)->assertCreated();

//     // get the team we posted
//     $group = Group::first();

//     expect(Str::isUlid($group->ulid))->toBeTrue();
// });

// test('a team can be viewed by ulid', function () {
//     // create a team
//     $group = Group::factory()->create();

//     // get the team
//     $this->get("api/v1/groups/{$group->ulid}")
//         ->assertOk()
//         ->assertJson(['data' => [
//             'designation' => $group->designation,
//             'mascot'      => $group->mascot,
//             'sport'       => $group->sport,
//             ]
//         ]);
// });

// test('a team cannot be viewed by id', function () {
//     // we want to catch the exception not see the pretty response
//     $this->withoutExceptionHandling();

//     // create a team
//     $group = Group::factory()->create();

//     // get the team
//     $this->get("api/v1/groups/{$group->id}");
// })->throws(ModelNotFoundException::class);

// test('a team can be updated', function () {
//     // create a team
//     $group = Group::factory()->create();

//     // set fields to update
//     $data = [
//         'designation' => 'updatedDesignation',
//         'mascot' => 'updatedMascot',
//     ];

//     // post the data
//     $this->patch("api/v1/groups/{$group->ulid}", $data)->assertNoContent();

//     $group->refresh();
    
//     expect($group->designation)->toBe($data['designation']);
//     expect($group->mascot)->toBe($data['mascot']);
// });

// test('a teams sport cannot be updated', function () {
//     // create a team
//     $group = Group::factory()->create();

//     // set fields to update
//     $data = [
//         'designation' => 'updatedDesignation',
//         'mascot' => 'updatedMascot',
//         'sport' => 'updatedSport',
//     ];

//     // post the data
//     $this->patch("api/v1/groups/{$group->ulid}", $data)->assertNoContent();

//     $group->refresh();
    
//     expect($group->sport)->not->toBe($data['sport']);
// });

// test('a lists of teams can be retrieved', function () {
//     // create 2 teams
//     [$group1, $group2] = Group::factory()->count(2)->create();

//     // get the teams
//     $this->get("api/v1/groups")
//         ->assertOk()
//         ->assertJson(['data' => [
//             [
//                 'designation' => $group1->designation,
//                 'mascot'      => $group1->mascot,
//                 'sport'       => $group1->sport,
//             ], [
//                 'designation' => $group2->designation,
//                 'mascot'      => $group2->mascot,
//                 'sport'       => $group2->sport,
//             ]
//         ]]);
// });

// test('a lists of teams can be filtered by sport', function () {
//     // create 2 basketball teams
//     [$group1, $group2] = Group::factory()->count(2)->create(['sport' => Sport::BASKETBALL->value]);

//     // create 2 football teams
//     [$group3, $group4] = Group::factory()->count(2)->create(['sport' => Sport::FOOTBALL->value]);

//     // get the basketball teams only
//     $this->get("api/v1/groups?sport=Basketball")
//         ->assertOk()
//         ->assertJsonCount(2, 'data')
//         ->assertJson(['data' => [
//             [
//                 'designation' => $group1->designation,
//                 'mascot'      => $group1->mascot,
//                 'sport'       => $group1->sport,
//             ], [
//                 'designation' => $group2->designation,
//                 'mascot'      => $group2->mascot,
//                 'sport'       => $group2->sport,
//             ]
//         ]]);
// });

// test('a lists of teams can be filtered by name for desigantion', function () {
//     // thing to find
//     $name = 'FindMe';

//     // create a team
//     $group = Group::factory()->create(['designation' => $name]);
//     $differentTeamToNotFind = Group::factory()->create(['designation' => 'somethingelse']);

//     // get the team
//     $this->get("api/v1/groups?name=$name")
//         ->assertOk()
//         ->assertJsonCount(1, 'data')
//         ->assertJson(['data' => [
//             [
//                 'designation' => $group->designation,
//                 'mascot'      => $group->mascot,
//                 'sport'       => $group->sport,
//             ]
//         ]]);
// });

// test('a lists of teams can be filtered by name for mascot', function () {
//     // thing to find
//     $name = 'FindMe';

//     // create a team
//     $group = Group::factory()->create(['mascot' => $name]);
//     $differentTeamToNotFind = Group::factory()->create(['mascot' => 'somethingelse']);

//     // get the team
//     $this->get("api/v1/groups?name=$name")
//         ->assertOk()
//         ->assertJsonCount(1, 'data')
//         ->assertJson(['data' => [
//             [
//                 'designation' => $group->designation,
//                 'mascot'      => $group->mascot,
//                 'sport'       => $group->sport,
//             ]
//         ]]);
// });

// test('a team can be deleted', function () {
//     // create a team
//     $group = Group::factory()->create();

//     // there should be 1 team in the db
//     $this->assertDatabaseCount('groups', 1);

//     // delete the team
//     $this->delete("api/v1/groups/{$group->ulid}")->assertAccepted();

//     // there should be no teams in the db
//     $this->assertDatabaseCount('groups', 0);
// });