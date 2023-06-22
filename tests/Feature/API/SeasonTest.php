<?php

use App\Models\Season;
use App\Models\SeasonType;
use App\Models\Sport;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a season can be created', function () {
    // make a season
    $seasonData = Season::factory()->make()->getAttributes();

    // there should be no seasons in the db
    $this->assertDatabaseCount('seasons', 0);

    // post the season data
    $this->post("api/v1/seasons", $seasonData)->assertOk();

    // there should be 1 season in the db
    $this->assertDatabaseCount('seasons', 1);

    // get the season we posted
    $season = Season::first();

    expect($season->name)->toBe($seasonData['name']);
    expect($season->sport)->toBe($seasonData['sport']);
    expect($season->season_type)->toBe($seasonData['season_type']);
    expect($season->season_start)->toBe($seasonData['season_start']);
    expect($season->season_end)->toBe($seasonData['season_end']);
});

test('the uuid field is populated when a season is created', function () {
    // make a season
    $seasonData = Season::factory()->make()->getAttributes();

    // there should be no seasons in the db
    $this->assertDatabaseCount('seasons', 0);

    // post the season data
    $this->post("api/v1/seasons", $seasonData)->assertOk();

    // there should be 1 season in the db
    $this->assertDatabaseCount('seasons', 1);

    // get the season we posted
    $season = Season::first();

    expect(Str::isUuid($season->uuid))->toBeTrue();
});

test('a season can be viewed by uuid', function () {
    // create a season
    $season = Season::factory()->create();

    // get the season
    $response = $this->get("api/v1/seasons/{$season->uuid}");

    $season->refresh();

    expect($response->json())->toBe(json_decode($season->toJson(), true));
});

test('a season cannot be viewed by id', function () {

    $this->withoutExceptionHandling();

    // create a season
    $season = Season::factory()->create();

    // get the season
    $this->get("api/v1/seasons/{$season->id}");
})->throws(ModelNotFoundException::class);

test('a season can be updated', function () {
    // create a season
    $season = Season::factory()->create();

    // set fields to update
    $data = [
        'name' => 'updatedName',
        'sport' => Sport::FOOTBALL->value,
        'season_type' => SeasonType::REGULAR->value,
        'season_start' => 'updatedSeason_start',
        'season_end' => 'updatedSeason_end',
    ];

    // post the data
    $this->patch("api/v1/seasons/{$season->uuid}", $data)->assertOk();

    $season->refresh();
    
    expect($season->name)->toBe($data['name']);
    expect($season->sport)->toBe($data['sport']);
    expect($season->season_type)->toBe($data['season_type']);
    expect($season->season_start)->toBe($data['season_start']);
    expect($season->season_end)->toBe($data['season_end']);
});