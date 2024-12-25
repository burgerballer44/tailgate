<?php

use App\Models\Game;
use App\Models\Season;
use App\Models\SeasonType;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

beforeEach(function() {
    $this->user = actAsAPIUser();
});

test('a season can be created', function () {
    // make values for a season
    $seasonData = Season::factory()->make()->getAttributes();

    // there should be no seasons in the db
    $this->assertDatabaseCount('seasons', 0);

    // post the season data
    $this->post("api/v1/seasons", $seasonData)->assertCreated();

    // there should be 1 season in the db
    $this->assertDatabaseCount('seasons', 1);
});

test('the season is returned when a season is created', function () {
    // make values for a season
    $seasonData = Season::factory()->make()->getAttributes();

    // post the season data
    $this->post("api/v1/seasons", $seasonData)
        ->assertCreated()
        ->assertJson(['data' => [
            'name'         => $seasonData['name'],
            'sport'        => $seasonData['sport'],
            'season_type'  => $seasonData['season_type'],
            'season_start' => $seasonData['season_start'],
            'season_end'   => $seasonData['season_end'],
            ]
        ]);
});

test('the ulid field is populated when a season is created', function () {
    // make values for a season
    $seasonData = Season::factory()->make()->getAttributes();

    // post the season data
    $this->post("api/v1/seasons", $seasonData)->assertCreated();

    // get the season we posted
    $season = Season::first();

    expect(Str::isUlid($season->ulid))->toBeTrue();
});

test('a season can be viewed by ulid', function () {
    // create a season
    $season = Season::factory()->create();

    // get the season
    $this->get("api/v1/seasons/{$season->ulid}")
        ->assertJson(['data' => [
            'name'         => $season->name,
            'sport'        => $season->sport,
            'season_type'  => $season->season_type,
            'season_start' => $season->season_start,
            'season_end'   => $season->season_end,
            ]
        ]);
});

test('a season cannot be viewed by id', function () {
    // we want to catch the exception not see the pretty response
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
    $this->patch("api/v1/seasons/{$season->ulid}", $data)->assertNoContent();

    $season->refresh();
    
    expect($season->name)->toBe($data['name']);
    expect($season->sport)->toBe($data['sport']);
    expect($season->season_type)->toBe($data['season_type']);
    expect($season->season_start)->toBe($data['season_start']);
    expect($season->season_end)->toBe($data['season_end']);
});

test('a lists of seasons can be retrieved', function () {
    // create 2 seasons
    [$season1, $season2] = season::factory()->count(2)->create();

    // get the seasons
    $this->get("api/v1/seasons")
        ->assertOk()
        ->assertJson(['data' => [
            [
                'name'         => $season1->name,
                'sport'        => $season1->sport,
                'season_type'  => $season1->season_type,
                'season_start' => $season1->season_start,
                'season_end'   => $season1->season_end,
            ], [
                'name'         => $season2->name,
                'sport'        => $season2->sport,
                'season_type'  => $season2->season_type,
                'season_start' => $season2->season_start,
                'season_end'   => $season2->season_end,
            ]
        ]]);
});

test('a lists of seasons can be filtered by sport', function () {
    // create 2 basketball seasons
    [$season1, $season2] = Season::factory()->count(2)->create(['sport' => Sport::BASKETBALL->value]);

    // create 2 football seasons
    [$season3, $season4] = Season::factory()->count(2)->create(['sport' => Sport::FOOTBALL->value]);

    // get the basketball seasons only
    $this->get("api/v1/seasons?sport=Basketball")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJson(['data' => [
            [
                'name'         => $season1->name,
                'sport'        => $season1->sport,
                'season_type'  => $season1->season_type,
                'season_start' => $season1->season_start,
                'season_end'   => $season1->season_end,
            ], [
                'name'         => $season2->name,
                'sport'        => $season2->sport,
                'season_type'  => $season2->season_type,
                'season_start' => $season2->season_start,
                'season_end'   => $season2->season_end,
            ]
        ]]);
});

test('a lists of seasons can be filtered by season_type', function () {
    // create 2 basketball seasons
    [$season1, $season2] = Season::factory()->count(2)->create(['season_type' => SeasonType::REGULAR->value]);

    // create 2 football seasons
    [$season3, $season4] = Season::factory()->count(2)->create(['season_type' => SeasonType::POST->value]);

    // get the regular seasons only
    $this->get("api/v1/seasons?season_type=Regular Season")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJson(['data' => [
            [
                'name'         => $season1->name,
                'sport'        => $season1->sport,
                'season_type'  => $season1->season_type,
                'season_start' => $season1->season_start,
                'season_end'   => $season1->season_end,
            ], [
                'name'         => $season2->name,
                'sport'        => $season2->sport,
                'season_type'  => $season2->season_type,
                'season_start' => $season2->season_start,
                'season_end'   => $season2->season_end,
            ]
        ]]);
});

test('a lists of seasons can be filtered by name for desigantion', function () {
    // thing to find
    $name = 'FindMe';

    // create a season
    $season = Season::factory()->create(['name' => $name]);
    $differentSeasonToNotFind = Season::factory()->create(['name' => 'somethingelse']);

    // get the season
    $this->get("api/v1/seasons?name=$name")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [
            [
                'name'         => $season->name,
                'sport'        => $season->sport,
                'season_type'  => $season->season_type,
                'season_start' => $season->season_start,
                'season_end'   => $season->season_end,
            ]
        ]]);
});

test('a season can be deleted', function () {
    // create a season
    $season = Season::factory()->create();

    // there should be 1 season in the db
    $this->assertDatabaseCount('seasons', 1);

    // delete the season
    $this->delete("api/v1/seasons/{$season->ulid}")->assertAccepted();

    // there should be no seasons in the db
    $this->assertDatabaseCount('seasons', 0);
});

test('all teams available for a season can be retrieved', function () {
    $sport = Sport::BASKETBALL->value;
    $wrongSport = Sport::FOOTBALL->value;

    // create a season
    $season = Season::factory()->create(['sport' => $sport]);

    // create teams of the same sport
    [$team1, $team2] = Team::factory()->count(2)->create(['sport' => $sport]);
    // create teams of a different sport
    [$wrongTeam1, $wrongTeam2] = Team::factory()->count(2)->create(['sport' => $wrongSport]);

    // get the season teams
    $this->get("api/v1/seasons/{$season->ulid}/teams")
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
            ],
            ]
        ]);
});