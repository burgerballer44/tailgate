<?php

use App\Models\Season;
use App\Models\SeasonType;
use App\Models\Sport;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = signInRegularUser();
});

describe('index', function () {
    test('page loads', function () {
        // create additional seasons
        $season1 = Season::factory()->create();
        $season2 = Season::factory()->create();

        // visit the index page
        $response = $this->get(route('admin.seasons.index'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.seasons.index');

        // assert data is passed to view
        $response->assertViewHas('seasons');
        $response->assertViewHas('sports');
        $response->assertViewHas('seasonTypes');

        // verify seasons are in the view data
        $viewSeasons = $response->viewData('seasons');
        expect($viewSeasons)->toHaveCount(2);

        // verify sports and seasonTypes are collections
        $sports = $response->viewData('sports');
        $seasonTypes = $response->viewData('seasonTypes');
        expect($sports)->toBeInstanceOf(Collection::class);
        expect($seasonTypes)->toBeInstanceOf(Collection::class);
    });

    test('seasons can be filtered by sport', function () {
        // create 2 basketball seasons
        [$season1, $season2] = Season::factory()->count(2)->create(['sport' => Sport::BASKETBALL->value]);

        // create 2 football seasons
        [$season3, $season4] = Season::factory()->count(2)->create(['sport' => Sport::FOOTBALL->value]);

        // get the basketball seasons only
        $response = $this->get(route('admin.seasons.index', ['sport' => 'Basketball']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.seasons.index');

        // assert seasons are filtered
        $response->assertViewHas('seasons');
        $seasons = $response->viewData('seasons');
        expect($seasons->count())->toBe(2);
    });
    
    test('seasons can be filtered by season_type', function () {
        // create 2 basketball seasons
        [$season1, $season2] = Season::factory()->count(2)->create(['season_type' => SeasonType::REGULAR->value]);

        // create 2 football seasons
        [$season3, $season4] = Season::factory()->count(2)->create(['season_type' => SeasonType::POST->value]);

        // get the regular seasons only
        $response = $this->get(route('admin.seasons.index', ['season_type' => 'Regular Season']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.seasons.index');

        // assert seasons are filtered
        $response->assertViewHas('seasons');
        $seasons = $response->viewData('seasons');
        expect($seasons->count())->toBe(2);
    });
    
    test('seasons can be filtered by q for name', function () {
        // thing to find
        $q = 'FindMe';

        // create a season
        $season = Season::factory()->create(['name' => $q]);
        $differentSeasonToNotFind = Season::factory()->create(['name' => 'somethingelse']);

        // get the season
        $response = $this->get(route('admin.seasons.index', ['q' => $q]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.seasons.index');

        // assert seasons are filtered
        $response->assertViewHas('seasons');
        $seasons = $response->viewData('seasons');
        expect($seasons->count())->toBe(1);
    });
    
});

describe('creating a season', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('admin.seasons.create'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.seasons.create');

        // assert data is passed to view
        $response->assertViewHas('sports');
        $response->assertViewHas('seasonTypes');

        // verify sports and seasonTypes are collections
        $sports = $response->viewData('sports');
        $seasonTypes = $response->viewData('seasonTypes');
        expect($sports)->toBeInstanceOf(Collection::class);
        expect($seasonTypes)->toBeInstanceOf(Collection::class);
    });

    test('works with valid data', function () {
        // season data
        $seasonData = Season::factory()->make()->toArray();

        // there should be 0 seasons in the db
        $this->assertDatabaseCount('seasons', 0);

        // post the season data
        $response = $this->post(route('admin.seasons.store'), $seasonData);

        // should redirect to index
        $response->assertRedirect(route('admin.seasons.index'));

        // there should be 1 season in the db
        $this->assertDatabaseCount('seasons', 1);

        // verify season was created
        $this->assertDatabaseHas('seasons', [
            'name' => $seasonData['name'],
            'sport' => $seasonData['sport'],
            'season_type' => $seasonData['season_type'],
            'season_start' => $seasonData['season_start'],
            'season_end' => $seasonData['season_end'],
            'active' => $seasonData['active'],
            'active_date' => Carbon::parse($seasonData['active_date'])->toDateTimeString(),
            'inactive_date' => Carbon::parse($seasonData['inactive_date'])->toDateTimeString(),
        ]);
    });

    test('flashes success message on store', function () {
        // season data
        $seasonData = Season::factory()->make()->toArray();

        // post the season data
        $this->post(route('admin.seasons.store'), $seasonData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Season created successfully!');
    });
});

describe('viewing a season', function () {
    test('works', function () {
        // create a season
        $season = Season::factory()->create();

        // visit the show page
        $response = $this->get(route('admin.seasons.show', $season));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.seasons.show');

        // assert season is passed to view
        $response->assertViewHas('season', $season);
    });
});

describe('updating season', function () {
    test('shows edit form', function () {
        // create a season
        $season = Season::factory()->create();

        // visit the edit page
        $response = $this->get(route('admin.seasons.edit', $season));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.seasons.edit');

        // assert data is passed to view
        $response->assertViewHas('season', $season);
        $response->assertViewHas('sports');
        $response->assertViewHas('seasonTypes');

        // verify sports and seasonTypes are collections
        $sports = $response->viewData('sports');
        $seasonTypes = $response->viewData('seasonTypes');
        expect($sports)->toBeInstanceOf(Collection::class);
        expect($seasonTypes)->toBeInstanceOf(Collection::class);
    });

    test('updates a season', function () {
        // create a season
        $season = Season::factory()->create([
            'name' => 'Original Name',
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2023-01-01',
            'season_end' => '2023-12-31',
            'active' => true,
            'active_date' => '2023-01-01',
            'inactive_date' => '2023-12-31',
        ]);

        // update data
        $updateData = [
            'name' => 'Updated Name',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::POST->value,
            'season_start' => '2024-01-01',
            'season_end' => '2024-12-31',
            'active' => false,
            'active_date' => '2024-01-01',
            'inactive_date' => '2024-12-31',
        ];

        // patch the season data
        $response = $this->patch(route('admin.seasons.update', $season), $updateData);

        // should redirect to index
        $response->assertRedirect(route('admin.seasons.index'));

        // verify season was updated
        $season->refresh();
        expect($season->name)->toBe($updateData['name']);
        expect($season->sport)->toBe($updateData['sport']);
        expect($season->season_type)->toBe($updateData['season_type']);
        expect($season->season_start)->toBe($updateData['season_start']);
        expect($season->season_end)->toBe($updateData['season_end']);
        expect($season->active)->toBe($updateData['active']);
        expect($season->active_date->toDateString())->toBe($updateData['active_date']);
        expect($season->inactive_date->toDateString())->toBe($updateData['inactive_date']);
    });

    test('flashes success message on update', function () {
        // create a season
        $season = Season::factory()->create();

        // update data
        $updateData = [
            'name' => 'Updated Name',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::POST->value,
            'season_start' => '2024-01-01',
            'season_end' => '2024-12-31',
            'active' => false,
            'active_date' => '2024-01-01',
            'inactive_date' => '2024-12-31',
        ];

        // patch the season data
        $this->patch(route('admin.seasons.update', $season), $updateData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Season updated successfully!');
    });
});

describe('deleting a season', function () {
    test('works', function () {
        // create a season
        $season = Season::factory()->create();

        // there should be 1 season in the db
        $this->assertDatabaseCount('seasons', 1);

        // delete the season
        $response = $this->delete(route('admin.seasons.destroy', $season));

        // should redirect to index
        $response->assertRedirect(route('admin.seasons.index'));

        // there should be 0 seasons in the db
        $this->assertDatabaseCount('seasons', 0);

        // verify season was deleted
        $this->assertDatabaseMissing('seasons', ['id' => $season->id]);
    });

    test('flashes success message on delete', function () {
        // create a season
        $season = Season::factory()->create();

        // delete the season
        $this->delete(route('admin.seasons.destroy', $season))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Season deleted successfully!');
    });
});