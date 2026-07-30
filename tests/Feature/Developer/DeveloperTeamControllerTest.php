<?php

use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
use App\Models\Enums\TeamType;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = signInDeveloperUser();
});

describe('index', function () {
    test('page loads', function () {
        // create additional teams
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // visit the index page
        $response = $this->get(route('developer.teams.index'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.teams.index');

        // assert data is passed to view
        $response->assertViewHas('teams');
        $response->assertViewHas('sports');

        // verify teams are in the view data
        $viewTeams = $response->viewData('teams');
        // including the signed in user, but teams are separate
        expect($viewTeams)->toHaveCount(2);

        // verify sports are collections
        $sports = $response->viewData('sports');
        expect($sports)->toBeInstanceOf(Collection::class);
    });

    test('lists of teams can be filtered by sport', function () {
        // create 2 basketball teams
        [$team1, $team2] = Team::factory()->withSports([Sport::BASKETBALL])->count(2)->create();

        // create 2 football teams
        [$team3, $team4] = Team::factory()->withSports([Sport::FOOTBALL])->count(2)->create();

        // get the basketball teams only
        $response = $this->get(route('developer.teams.index', ['sport' => 'Basketball']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.teams.index');

        // assert teams are filtered
        $response->assertViewHas('teams');
        $teams = $response->viewData('teams');
        expect($teams->count())->toBe(2);
    });

    test('lists of teams can be filtered by type', function () {
        // create 2 college teams
        [$team1, $team2] = Team::factory()->count(2)->create(['type' => TeamType::COLLEGE]);

        // create 2 professional teams
        [$team3, $team4] = Team::factory()->count(2)->create(['type' => TeamType::PROFESSIONAL]);

        // get the college teams only
        $response = $this->get(route('developer.teams.index', ['type' => 'College']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.teams.index');

        // assert teams are filtered
        $response->assertViewHas('teams');
        $teams = $response->viewData('teams');
        expect($teams->count())->toBe(2);
    });

    test('lists of teams can be filtered by q for designation', function () {
        // thing to find
        $q = 'FindMe';

        // create a team
        $team = Team::factory()->withSports([Sport::BASKETBALL])->create(['designation' => $q]);
        $differentTeamToNotFind = Team::factory()->withSports([Sport::BASKETBALL])->create(['designation' => 'somethingelse']);

        // get the team
        $response = $this->get(route('developer.teams.index', ['q' => $q]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.teams.index');

        // assert teams are filtered
        $response->assertViewHas('teams');
        $teams = $response->viewData('teams');
        expect($teams->count())->toBe(1);
    });

    test('lists of teams can be filtered by q for organization', function () {
        // thing to find
        $q = 'FindMe';

        // create a team
        $team = Team::factory()->withSports([Sport::BASKETBALL])->create(['organization' => $q]);
        $differentTeamToNotFind = Team::factory()->withSports([Sport::BASKETBALL])->create(['organization' => 'somethingelse']);

        // get the team
        $response = $this->get(route('developer.teams.index', ['q' => $q]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.teams.index');

        // assert teams are filtered
        $response->assertViewHas('teams');
        $teams = $response->viewData('teams');
        expect($teams->count())->toBe(1);
    });
});

describe('creating a team', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('developer.teams.create'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.teams.create');

        // assert data is passed to view
        $response->assertViewHas('sports');

        // verify sports are collections
        $sports = $response->viewData('sports');
        expect($sports)->toBeInstanceOf(Collection::class);
    });

    test('will create a team', function () {
        // team data
        $teamData = [
            'organization' => 'Test Organization',
            'designation' => 'Test Team',
            'conference' => 'SEC',
            'abbreviation' => 'TT',
            'color' => '#8c2232',
            'logos' => json_encode(['https://example.test/team-logo.png']),
            'social_media' => json_encode([['label' => 'X', 'url' => 'https://x.com/test-team']]),
            'type' => TeamType::COLLEGE->value,
            'sports' => [Sport::BASKETBALL->value],
        ];

        // there should be 0 teams in the db
        $this->assertDatabaseCount('teams', 0);

        // post the team data
        $response = $this->post(route('developer.teams.store'), $teamData);

        // should redirect to index
        $response->assertRedirect(route('developer.teams.index'));

        // there should be 1 team in the db
        $this->assertDatabaseCount('teams', 1);

        // verify team was created
        $this->assertDatabaseHas('teams', [
            'organization' => $teamData['organization'],
            'designation' => $teamData['designation'],
            'type' => $teamData['type'],
        ]);

        $this->assertDatabaseHas('team_sports', [
            'sport' => Sport::BASKETBALL->value,
            'conference' => $teamData['conference'],
        ]);

        // verify team sports were created
        $team = Team::first();
        expect($team->sports->pluck('sport')->toArray())->toBe([Sport::BASKETBALL]);
    });

    test('flashes success message on store', function () {
        // team data
        $teamData = [
            'organization' => 'Test Organization',
            'designation' => 'Test Team',
            'conference' => 'SEC',
            'abbreviation' => 'TT',
            'color' => '#8c2232',
            'logos' => json_encode(['https://example.test/team-logo.png']),
            'social_media' => json_encode([['label' => 'X', 'url' => 'https://x.com/test-team']]),
            'type' => TeamType::COLLEGE->value,
            'sports' => [Sport::BASKETBALL->value],
        ];

        // post the team data
        $this->post(route('developer.teams.store'), $teamData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Team created successfully!');
    });
});

describe('viewing a team', function () {
    test('works', function () {
        // create a team
        $team = Team::factory()->create([
            'organization' => 'North Carolina',
            'designation' => 'Tar Heels',
            'abbreviation' => 'UNC',
        ]);

        // visit the show page
        $response = $this->get(route('developer.teams.show', $team));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.teams.show');

        // assert team is passed to view
        $response->assertViewHas('team', $team);
        $response->assertSee($team->display_name);
    });
});

describe('updating team', function () {
    test('shows edit form', function () {
        // create a team
        $team = Team::factory()->create();

        // visit the edit page
        $response = $this->get(route('developer.teams.edit', $team));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.teams.edit');

        // assert data is passed to view
        $response->assertViewHas('team', $team);
        $response->assertViewHas('sports');

        // verify sports are collections
        $sports = $response->viewData('sports');
        expect($sports)->toBeInstanceOf(Collection::class);
    });

    test('updates a team', function () {
        // create a team
        $team = Team::factory()->withSports([Sport::BASKETBALL])->create([
            'organization' => 'theOrganization',
            'designation' => 'theDesignation',
            'type' => TeamType::COLLEGE,
        ]);

        // update dataX
        $updateData = [
            'organization' => 'Updated Organization',
            'designation' => 'Updated Designation',
            'conference' => 'ACC',
            'abbreviation' => 'UD',
            'color' => '#123456',
            'logos' => json_encode(['https://example.test/new-logo.png']),
            'social_media' => json_encode([['label' => 'Instagram', 'url' => 'https://instagram.com/new-team']]),
            'type' => TeamType::PROFESSIONAL->value,
            'sports' => [Sport::FOOTBALL->value],
        ];

        // patch the team data
        $response = $this->patch(route('developer.teams.update', $team), $updateData);

        // should redirect to index
        $response->assertRedirect(route('developer.teams.index'));

        // verify team was updated
        $team->refresh();
        expect($team->organization)->toBe($updateData['organization']);
        expect($team->designation)->toBe($updateData['designation']);
        expect($team->conference)->toBe($updateData['conference']);
        expect($team->type)->toBe($updateData['type']);
        expect($team->sports->pluck('sport')->toArray())->toBe([Sport::FOOTBALL]);
    });

    test('flashes success message on update', function () {
        // create a team
        $team = Team::factory()->create();

        // update data
        $updateData = [
            'organization' => 'Updated Organization',
            'designation' => 'Updated Designation',
            'conference' => 'ACC',
            'abbreviation' => 'UD',
            'color' => '#123456',
            'logos' => json_encode(['https://example.test/new-logo.png']),
            'social_media' => json_encode([['label' => 'Instagram', 'url' => 'https://instagram.com/new-team']]),
            'type' => TeamType::PROFESSIONAL->value,
            'sports' => [Sport::FOOTBALL->value],
        ];

        // patch the team data
        $this->patch(route('developer.teams.update', $team), $updateData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Team updated successfully!');
    });
});

describe('deleting a team', function () {
    test('works', function () {
        // create a team
        $team = Team::factory()->create();

        // there should be 1 team in the db
        $this->assertDatabaseCount('teams', 1);

        // delete the team
        $response = $this->delete(route('developer.teams.destroy', $team));

        // should redirect to index
        $response->assertRedirect(route('developer.teams.index'));

        // there should be 0 teams in the db
        $this->assertDatabaseCount('teams', 0);

        // verify team was deleted
        $this->assertDatabaseMissing('teams', ['ulid' => $team->ulid]);
    });

    test('flashes success message on delete', function () {
        // create a team
        $team = Team::factory()->create();

        // delete the team
        $this->delete(route('developer.teams.destroy', $team))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Team deleted successfully!');
    });
});

describe('importing teams', function () {
    test('shows import teams form', function () {
        $response = $this->get(route('developer.teams.import-teams'));

        $response->assertOk();
        $response->assertViewIs('developer.teams.import-teams');
        $response->assertViewHas('sources');
    });

    test('imports teams from cfbd for a selected season', function () {
        config()->set('services.import.cfbd.token', 'test-token');

        $season = Season::factory()->create([
            'name' => 'Football Season 2026',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
        ]);

        Http::fake([
            'https://api.collegefootballdata.com/teams*' => Http::response([
                [
                    'id' => 2000,
                    'school' => 'Abilene Christian',
                    'mascot' => 'Wildcats',
                    'abbreviation' => 'ACU',
                    'conference' => 'UAC',
                    'color' => '#592d82',
                    'logos' => ['https://example.test/acu-primary.png'],
                    'twitter' => '@ACUFootball',
                ],
            ]),
        ]);

        $response = $this->post(route('developer.teams.import-teams.store'), [
            'season_id' => $season->id,
            'source' => 'cfbd',
            'year' => 2026,
        ]);

        $response->assertRedirect(route('developer.teams.index'));

        $this->assertDatabaseHas('teams', [
            'organization' => 'Abilene Christian',
            'designation' => 'Wildcats',
            'abbreviation' => 'ACU',
        ]);

        $this->assertDatabaseHas('team_sports', [
            'sport' => Sport::FOOTBALL->value,
            'conference' => 'UAC',
        ]);

        expect(session('alert')['type'])->toBe('success');
        expect(session('alert')['message'])->toBe('Imported 1 team(s) from CFBD API.');
    });

    test('updates existing team metadata when import finds an existing team for season sport', function () {
        config()->set('services.import.cfbd.token', 'test-token');

        $season = Season::factory()->create([
            'name' => 'Football Season 2026',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
        ]);

        $team = Team::factory()->withSports([Sport::FOOTBALL->value])->create([
            'organization' => 'Abilene Christian',
            'designation' => 'Old Name',
            'abbreviation' => 'OLD',
        ]);

        Http::fake([
            'https://api.collegefootballdata.com/teams*' => Http::response([
                [
                    'id' => 2000,
                    'school' => 'Abilene Christian',
                    'mascot' => 'Wildcats',
                    'abbreviation' => 'ACU',
                    'conference' => 'UAC',
                    'color' => '#592d82',
                    'logos' => ['https://example.test/acu-primary.png'],
                    'twitter' => '@ACUFootball',
                ],
            ]),
        ]);

        $response = $this->post(route('developer.teams.import-teams.store'), [
            'season_id' => $season->id,
            'source' => 'cfbd',
            'year' => 2026,
        ]);

        $response->assertRedirect(route('developer.teams.index'));

        $team->refresh();

        expect($team->designation)->toBe('Wildcats');
        expect($team->conference)->toBe('UAC');
        expect($team->abbreviation)->toBe('ACU');

        $this->assertDatabaseCount('teams', 1);
        expect(session('alert')['type'])->toBe('success');
        expect(session('alert')['message'])->toBe('Updated 1 existing team(s) from CFBD API.');
    });

    test('shows warning when team import is partially successful', function () {
        config()->set('services.import.cfbd.token', 'test-token');

        Http::fake([
            'https://api.collegefootballdata.com/teams*' => Http::response([
                [
                    'id' => 2000,
                    'school' => 'Abilene Christian',
                    'mascot' => 'Wildcats',
                    'abbreviation' => 'ACU',
                    'conference' => 'UAC',
                    'color' => '#592d82',
                    'logos' => ['https://example.test/acu-primary.png'],
                    'twitter' => '@ACUFootball',
                ],
                [
                    'id' => 2001,
                    'school' => 'Broken Team',
                    'conference' => '',
                ],
            ]),
        ]);

        $response = $this->post(route('developer.teams.import-teams.store'), [
            'source' => 'cfbd',
            'year' => 2026,
        ]);

        $response->assertRedirect(route('developer.teams.index'));

        $this->assertDatabaseHas('teams', [
            'organization' => 'Abilene Christian',
            'abbreviation' => 'ACU',
        ]);

        $this->assertDatabaseHas('team_sports', [
            'sport' => Sport::FOOTBALL->value,
            'conference' => 'UAC',
        ]);

        expect(session('alert')['type'])->toBe('warning');
        expect(session('alert')['message'])->toBeArray();
        expect(session('alert')['message'][0])->toBe('Imported 1 team(s) from CFBD API.');
        expect(session('alert')['message'][1])->toContain('Skipped CFBD team 2001: required fields were missing from the response.');
    });

    test('shows error alert when team import request validation fails', function () {
        $response = $this->from(route('developer.teams.import-teams'))
            ->post(route('developer.teams.import-teams.store'), [
                'source' => 'not-a-source',
                'year' => 2026,
            ]);

        $response->assertRedirect(route('developer.teams.import-teams'));
        $this->assertDatabaseCount('teams', 0);
        expect(session('alert')['type'])->toBe('error');
        expect(session('alert')['message'])->toBe('Team import failed due to an unexpected error.');
    });
});
