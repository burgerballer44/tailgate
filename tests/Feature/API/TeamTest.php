<?php

use App\Models\Team;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a team can be created', function () {
    // make a team
    $teamData = Team::factory()->make()->getAttributes();

    // there should be no teams in the db
    $this->assertDatabaseCount('teams', 0);

    // post the team data
    $this->post("api/v1/teams", $teamData)->assertOk();

    // there should be 1 team in the db
    $this->assertDatabaseCount('teams', 1);

    // get the team we posted
    $team = Team::first();

    expect($team->designation)->toBe($teamData['designation']);
    expect($team->mascot)->toBe($teamData['mascot']);
    expect($team->sport)->toBe($teamData['sport']);
});

test('the uuid field is populated when a team is created', function () {
    // make a team
    $teamData = Team::factory()->make()->getAttributes();

    // there should be no teams in the db
    $this->assertDatabaseCount('teams', 0);

    // post the team data
    $this->post("api/v1/teams", $teamData)->assertOk();

    // there should be 1 team in the db
    $this->assertDatabaseCount('teams', 1);

    // get the team we posted
    $team = Team::first();

    expect(Str::isUuid($team->uuid))->toBeTrue();
});

test('a team can be viewed by uuid', function () {
    // create a team
    $team = Team::factory()->create();

    // get the team
    $response = $this->get("api/v1/teams/{$team->uuid}");

    $team->refresh();

    expect($response->json())->toBe(json_decode($team->toJson(), true));
});

test('a team cannot be viewed by id', function () {

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
    $this->patch("api/v1/teams/{$team->uuid}", $data)->assertOk();

    $team->refresh();
    
    expect($team->designation)->toBe($data['designation']);
    expect($team->mascot)->toBe($data['mascot']);
});