<?php

use App\Models\Team;
use App\Models\Sport;
use App\Services\TeamService;
use App\DTO\ValidatedTeamData;

beforeEach(function () {
    $this->teamService = new TeamService();
});

describe('create a team', function () {
    test('with valid data', function () {
        // create team data
        $data = Team::factory()->make()->toArray();

        // ensure team does not exist
        $this->assertDatabaseMissing('teams', ['designation' => $data['designation']]);

        // try to create the team
        $team = $this->teamService->create(ValidatedTeamData::fromArray($data));

        // verify team exists in database
        $this->assertDatabaseHas('teams', ['designation' => $data['designation']]);

        expect($team)->toBeInstanceOf(Team::class);
        expect($team->designation)->toBe($data['designation']);
        expect($team->mascot)->toBe($data['mascot']);
        expect($team->sport)->toBe($data['sport']);
    });
});

describe('update a team', function () {
    test('with valid data', function () {
        // create existing team
        $team = Team::factory()->create([
            'designation' => 'Old Designation',
            'mascot' => 'Old Mascot',
            'sport' => Sport::FOOTBALL->value,
        ]);

        // data to update to
        $data = ValidatedTeamData::fromArray([
            'designation' => 'New Designation',
            'mascot' => 'New Mascot',
            'sport' => Sport::BASKETBALL,
        ]);

        // try to update the team
        $this->teamService->update($team, $data);

        $team->refresh();
        expect($team->designation)->toBe($data->designation);
        expect($team->mascot)->toBe($data->mascot);
        expect($team->sport)->toBe($data->sport->value);
    });

    test('does not update null values', function () {
        // create existing team
        $team = Team::factory()->create([
            'designation' => 'Original Designation',
            'mascot' => 'Original Mascot',
            'sport' => Sport::FOOTBALL->value,
        ]);

        // data to update to with nulls
        $data = ValidatedTeamData::fromArray([
            'designation' => null,
            'mascot' => 'Updated Mascot',
            'sport' => null,
        ]);

        //
        $this->teamService->update($team, $data);

        $team->refresh();
        expect($team->designation)->toBe($team->designation);
        expect($team->mascot)->toBe($data->mascot);
        expect($team->sport)->toBe($team->sport);
    });
});

describe('delete', function () {
    test('deletes a team', function () {
        // create a team
        $team = Team::factory()->create();

        // delete the team
        $this->teamService->delete($team);

        // verify team is deleted from database
        expect(Team::find($team->id))->toBeNull();
    });
});