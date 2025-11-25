<?php

use App\Models\Team;
use App\Models\Sport;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->user = signInRegularUser();
});

describe('index', function () {
    test('works', function () {
        // create additional teams
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // visit the index page
        $response = $this->get(route('teams.index'));

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
});

describe('creating a team', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('teams.create'));

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

    test('works', function () {
        // team data
        $teamData = Team::factory()->make()->toArray();

        // there should be 0 teams in the db
        $this->assertDatabaseCount('teams', 0);

        // post the team data
        $response = $this->post(route('teams.store'), $teamData);

        // should redirect to index
        $response->assertRedirect(route('teams.index'));

        // there should be 1 team in the db
        $this->assertDatabaseCount('teams', 1);

        // verify team was created
        $this->assertDatabaseHas('teams', [
            'designation' => $teamData['designation'],
            'mascot' => $teamData['mascot'],
            'sport' => $teamData['sport'],
        ]);
    });

    test('flashes success message on store', function () {
        // team data
        $teamData = Team::factory()->make()->toArray();

        // post the team data
        $this->post(route('teams.store'), $teamData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Team created successfully!');
    });
});

describe('viewing a team', function () {
    test('works', function () {
        // create a team
        $team = Team::factory()->create();

        // visit the show page
        $response = $this->get(route('teams.show', $team));

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
        $response = $this->get(route('teams.edit', $team));

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
        $team = Team::factory()->create([
            'designation' => 'theDesignation',
            'mascot' => 'theMascot',
            'sport' => Sport::BASKETBALL->value,
        ]);

        // update data
        $updateData = [
            'designation' => 'Updated Designation',
            'mascot' => 'Updated Mascot',
        ];

        // patch the team data
        $response = $this->patch(route('teams.update', $team), $updateData);

        // should redirect to index
        $response->assertRedirect(route('teams.index'));

        // verify team was updated
        $team->refresh();
        expect($team->designation)->toBe($updateData['designation']);
        expect($team->mascot)->toBe($updateData['mascot']);
    });

    test('sport of a team cannot be updated', function () {
        // create a team
        $team = Team::factory()->create([
            'sport' => Sport::BASKETBALL->value,
        ]);
    
        // update data
        $updateData = [
            'sport' => Sport::FOOTBALL->value,
        ];
    
        // patch the team data
        $response = $this->patch(route('teams.update', $team), $updateData);
    
        // should redirect to index
        $response->assertRedirect(route('teams.index'));
    
        // verify team was updated
        $team->refresh();
        expect($team->sport)->not->toBe($updateData['sport']);
    });

    test('flashes success message on update', function () {
        // create a team
        $team = Team::factory()->create();

        // update data
        $updateData = [
            'designation' => 'Updated Designation',
            'mascot' => 'Updated Mascot',
            'sport' => Sport::FOOTBALL->value,
        ];

        // patch the team data
        $this->patch(route('teams.update', $team), $updateData)->assertRedirect();

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
        $response = $this->delete(route('teams.destroy', $team));

        // should redirect to index
        $response->assertRedirect(route('teams.index'));

        // there should be 0 teams in the db
        $this->assertDatabaseCount('teams', 0);

        // verify team was deleted
        $this->assertDatabaseMissing('teams', ['ulid' => $team->ulid]);
    });

    test('flashes success message on delete', function () {
        // create a team
        $team = Team::factory()->create();

        // delete the team
        $this->delete(route('teams.destroy', $team))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Team deleted successfully!');
    });
});