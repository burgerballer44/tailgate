<?php

use App\Models\Team;
use App\Models\Sport;
use Illuminate\Support\Str;
use App\Services\TeamService;
use App\DTO\ValidatedTeamData;

beforeEach(function () {
    $this->service = new TeamService();
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
        $team = $this->service->create(ValidatedTeamData::fromArray($data));

        // verify team exists in database
        $this->assertDatabaseHas('teams', ['designation' => $data['designation']]);

        expect($team)->toBeInstanceOf(Team::class);
        expect($team->designation)->toBe($data['designation']);
        expect($team->mascot)->toBe($data['mascot']);
        expect($team->sports->pluck('sport')->toArray())->toBe([Sport::BASKETBALL]);
        expect(Str::isUlid((string)$team->ulid))->toBeTrue();
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

        // ensure updated team does not exist
        $this->assertDatabaseMissing('teams', [
            'designation' => $data->designation,
            'mascot' => $data->mascot,
        ]);

        // try to update the team
        $updatedTeam = $this->service->update($team, $data);

        // verify updated team exists in database
        $this->assertDatabaseHas('teams', [
            'designation' => $data->designation,
            'mascot' => $data->mascot,
        ]);

        // verify returned team is the same instance
        expect($updatedTeam)->toBe($team);

        // verify updated data
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

        // try to update the team
        $updatedTeam = $this->service->update($team, $data);

        // verify returned team is the same instance
        expect($updatedTeam)->toBe($team);

        // verify data is unchanged
        expect($updatedTeam->toArray())->toBe($team->toArray());
    });
});

describe('delete', function () {
    test('deletes a team', function () {
        // create a team
        $team = Team::factory()->create();

        // delete the team
        $this->service->delete($team);

        // verify team is deleted from database
        expect(Team::find($team->id))->toBeNull();
    });
});