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
        $data = [
            'designation' => 'Test Team',
            'mascot' => 'Test Mascot',
            'sports' => [Sport::BASKETBALL->value],
        ];

        // ensure team does not exist
        $this->assertDatabaseMissing('teams', ['designation' => $data['designation']]);

        // try to create the team
        $team = $this->teamService->create(ValidatedTeamData::fromArray($data));

        // verify team exists in database
        $this->assertDatabaseHas('teams', ['designation' => $data['designation']]);

        expect($team)->toBeInstanceOf(Team::class);
        expect($team->designation)->toBe($data['designation']);
        expect($team->mascot)->toBe($data['mascot']);
        expect($team->sports->pluck('sport')->toArray())->toBe([Sport::BASKETBALL]);
    });
});

describe('update a team', function () {
    test('with valid data', function () {
        // create existing team
        $team = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'designation' => 'Old Designation',
            'mascot' => 'Old Mascot'
        ]);

        // data to update to
        $data = ValidatedTeamData::fromArray([
            'designation' => 'New Designation',
            'mascot' => 'New Mascot',
            'sports' => [Sport::BASKETBALL->value],
        ]);

        // try to update the team
        $this->teamService->update($team, $data);

        $team->refresh();
        expect($team->designation)->toBe($data->designation);
        expect($team->mascot)->toBe($data->mascot);
        expect($team->sports->pluck('sport')->toArray())->toBe($data->sports);
    });

    test('does not update null values', function () {
        // create existing team
        $team = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'designation' => 'Original Designation',
            'mascot' => 'Original Mascot',
        ]);

        // data to update to with nulls
        $data = ValidatedTeamData::fromArray([
            'designation' => null,
            'mascot' => 'Updated Mascot',
            'sports' => null,
        ]);

        //
        $this->teamService->update($team, $data);

        $team->refresh();
        expect($team->designation)->toBe($team->designation);
        expect($team->mascot)->toBe($data->mascot);
        expect($team->sports->pluck('sport')->toArray())->toBe([Sport::FOOTBALL]);
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