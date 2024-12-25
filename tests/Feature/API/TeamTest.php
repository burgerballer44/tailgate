<?php

use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

beforeEach(function() {
    $this->user = actAsAPIUser();
});

test('a team can be created', function () {
    // make data for a team
    $teamData = Team::factory()->make()->getAttributes();

    // there should be no teams in the db
    $this->assertDatabaseCount('teams', 0);

    // post the team data
    $this->post("api/v1/teams", $teamData)->assertCreated();

    // there should be 1 team in the db
    $this->assertDatabaseCount('teams', 1);
});

test('the team is returned when a team is created', function () {
    // make data for a team
    $teamData = Team::factory()->make()->getAttributes();

    // post the team data
    $this->post("api/v1/teams", $teamData)
        ->assertCreated()
        ->assertJson(['data' => [
            'designation' => $teamData['designation'],
            'mascot'      => $teamData['mascot'],
            'sport'       => $teamData['sport'],
            ]
        ]);
});

test('the ulid field is populated when a team is created', function () {
    // make data for a team
    $teamData = Team::factory()->make()->getAttributes();

    // post the team data
    $this->post("api/v1/teams", $teamData)->assertCreated();

    // get the team we posted
    $team = Team::first();

    expect(Str::isUlid($team->ulid))->toBeTrue();
});

test('a team can be viewed by ulid', function () {
    // create a team
    $team = Team::factory()->create();

    // get the team
    $this->get("api/v1/teams/{$team->ulid}")
        ->assertOk()
        ->assertJson(['data' => [
            'designation' => $team->designation,
            'mascot'      => $team->mascot,
            'sport'       => $team->sport,
            ]
        ]);
});

test('a team cannot be viewed by id', function () {
    // we want to catch the exception not see the pretty response
    $this->withoutExceptionHandling();

    // create a team
    $team = Team::factory()->create();

    // get the team
    $this->get("api/v1/teams/{$team->id}");
})->throws(ModelNotFoundException::class);

test('a team can be updated', function () {
    // create a team
    $team = Team::factory()->create();

    // set fields to update
    $data = [
        'designation' => 'updatedDesignation',
        'mascot' => 'updatedMascot',
    ];

    // post the data
    $this->patch("api/v1/teams/{$team->ulid}", $data)->assertNoContent();

    $team->refresh();
    
    expect($team->designation)->toBe($data['designation']);
    expect($team->mascot)->toBe($data['mascot']);
});

test('a teams sport cannot be updated', function () {
    // create a team
    $team = Team::factory()->create();

    // set fields to update
    $data = [
        'designation' => 'updatedDesignation',
        'mascot' => 'updatedMascot',
        'sport' => 'updatedSport',
    ];

    // post the data
    $this->patch("api/v1/teams/{$team->ulid}", $data)->assertNoContent();

    $team->refresh();
    
    expect($team->sport)->not->toBe($data['sport']);
});

test('a lists of teams can be retrieved', function () {
    // create 2 teams
    [$team1, $team2] = Team::factory()->count(2)->create();

    // get the teams
    $this->get("api/v1/teams")
        ->assertOk()
        ->assertJson(['data' => [
            [
                'designation' => $team1->designation,
                'mascot'      => $team1->mascot,
                'sport'       => $team1->sport,
            ], [
                'designation' => $team2->designation,
                'mascot'      => $team2->mascot,
                'sport'       => $team2->sport,
            ]
        ]]);
});

test('a lists of teams can be filtered by sport', function () {
    // create 2 basketball teams
    [$team1, $team2] = Team::factory()->count(2)->create(['sport' => Sport::BASKETBALL->value]);

    // create 2 football teams
    [$team3, $team4] = Team::factory()->count(2)->create(['sport' => Sport::FOOTBALL->value]);

    // get the basketball teams only
    $this->get("api/v1/teams?sport=Basketball")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJson(['data' => [
            [
                'designation' => $team1->designation,
                'mascot'      => $team1->mascot,
                'sport'       => $team1->sport,
            ], [
                'designation' => $team2->designation,
                'mascot'      => $team2->mascot,
                'sport'       => $team2->sport,
            ]
        ]]);
});

test('a lists of teams can be filtered by name for desigantion', function () {
    // thing to find
    $name = 'FindMe';

    // create a team
    $team = Team::factory()->create(['designation' => $name]);
    $differentTeamToNotFind = Team::factory()->create(['designation' => 'somethingelse']);

    // get the team
    $this->get("api/v1/teams?name=$name")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [
            [
                'designation' => $team->designation,
                'mascot'      => $team->mascot,
                'sport'       => $team->sport,
            ]
        ]]);
});

test('a lists of teams can be filtered by name for mascot', function () {
    // thing to find
    $name = 'FindMe';

    // create a team
    $team = Team::factory()->create(['mascot' => $name]);
    $differentTeamToNotFind = Team::factory()->create(['mascot' => 'somethingelse']);

    // get the team
    $this->get("api/v1/teams?name=$name")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [
            [
                'designation' => $team->designation,
                'mascot'      => $team->mascot,
                'sport'       => $team->sport,
            ]
        ]]);
});

test('a team can be deleted', function () {
    // create a team
    $team = Team::factory()->create();

    // there should be 1 team in the db
    $this->assertDatabaseCount('teams', 1);

    // delete the team
    $this->delete("api/v1/teams/{$team->ulid}")->assertAccepted();

    // there should be no teams in the db
    $this->assertDatabaseCount('teams', 0);
});