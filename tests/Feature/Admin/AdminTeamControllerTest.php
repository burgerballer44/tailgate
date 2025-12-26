<?php

use App\Models\Team;
use App\Models\Sport;
use App\Models\TeamType;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->user = signInAdminUser();
});

describe('index', function () {
    test('page loads', function () {
        // create additional teams
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // visit the index page
        $response = $this->get(route('admin.teams.index'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.index');

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
        $response = $this->get(route('admin.teams.index', ['sport' => 'Basketball']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.index');

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
        $response = $this->get(route('admin.teams.index', ['type' => 'College']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.index');

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
        $response = $this->get(route('admin.teams.index', ['q' => $q]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.index');

        // assert teams are filtered
        $response->assertViewHas('teams');
        $teams = $response->viewData('teams');
        expect($teams->count())->toBe(1);
    });
    
    test('lists of teams can be filtered by q for mascot', function () {
        // thing to find
        $q = 'FindMe';

        // create a team
        $team = Team::factory()->withSports([Sport::BASKETBALL])->create(['mascot' => $q]);
        $differentTeamToNotFind = Team::factory()->withSports([Sport::BASKETBALL])->create(['mascot' => 'somethingelse']);

        // get the team
        $response = $this->get(route('admin.teams.index', ['q' => $q]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.index');

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
        $response = $this->get(route('admin.teams.index', ['q' => $q]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.index');

        // assert teams are filtered
        $response->assertViewHas('teams');
        $teams = $response->viewData('teams');
        expect($teams->count())->toBe(1);
    });
});

describe('creating a team', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('admin.teams.create'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.create');

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
            'mascot' => 'Test Mascot',
            'type' => TeamType::COLLEGE->value,
            'sports' => [Sport::BASKETBALL->value],
        ];

        // there should be 0 teams in the db
        $this->assertDatabaseCount('teams', 0);

        // post the team data
        $response = $this->post(route('admin.teams.store'), $teamData);

        // should redirect to index
        $response->assertRedirect(route('admin.teams.index'));

        // there should be 1 team in the db
        $this->assertDatabaseCount('teams', 1);

        // verify team was created
        $this->assertDatabaseHas('teams', [
            'organization' => $teamData['organization'],
            'designation' => $teamData['designation'],
            'mascot' => $teamData['mascot'],
            'type' => $teamData['type'],
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
            'mascot' => 'Test Mascot',
            'type' => TeamType::COLLEGE->value,
            'sports' => [Sport::BASKETBALL->value],
        ];

        // post the team data
        $this->post(route('admin.teams.store'), $teamData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Team created successfully!');
    });
});

describe('viewing a team', function () {
    test('works', function () {
        // create a team
        $team = Team::factory()->create();

        // visit the show page
        $response = $this->get(route('admin.teams.show', $team));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.show');

        // assert team is passed to view
        $response->assertViewHas('team', $team);
    });
});

describe('updating team', function () {
    test('shows edit form', function () {
        // create a team
        $team = Team::factory()->create();

        // visit the edit page
        $response = $this->get(route('admin.teams.edit', $team));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.teams.edit');

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
            'mascot' => 'theMascot',
            'type' => TeamType::COLLEGE,
        ]);

        // update dataX
        $updateData = [
            'organization' => 'Updated Organization',
            'designation' => 'Updated Designation',
            'mascot' => 'Updated Mascot',
            'type' => TeamType::PROFESSIONAL->value,
            'sports' => [Sport::FOOTBALL->value],
        ];

        // patch the team data
        $response = $this->patch(route('admin.teams.update', $team), $updateData);

        // should redirect to index
        $response->assertRedirect(route('admin.teams.index'));

        // verify team was updated
        $team->refresh();
        expect($team->organization)->toBe($updateData['organization']);
        expect($team->designation)->toBe($updateData['designation']);
        expect($team->mascot)->toBe($updateData['mascot']);
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
            'mascot' => 'Updated Mascot',
            'type' => TeamType::PROFESSIONAL->value,
            'sports' => [Sport::FOOTBALL->value],
        ];

        // patch the team data
        $this->patch(route('admin.teams.update', $team), $updateData)->assertRedirect();

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
        $response = $this->delete(route('admin.teams.destroy', $team));

        // should redirect to index
        $response->assertRedirect(route('admin.teams.index'));

        // there should be 0 teams in the db
        $this->assertDatabaseCount('teams', 0);

        // verify team was deleted
        $this->assertDatabaseMissing('teams', ['ulid' => $team->ulid]);
    });

    test('flashes success message on delete', function () {
        // create a team
        $team = Team::factory()->create();

        // delete the team
        $this->delete(route('admin.teams.destroy', $team))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Team deleted successfully!');
    });
});