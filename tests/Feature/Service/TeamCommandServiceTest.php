<?php

use App\DTO\ValidatedTeamData;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamType;
use App\Services\TeamCommandService;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = new TeamCommandService;
});

describe('create a team', function () {
    test('with valid data', function () {
        // create team data
        $data = [
            'organization' => 'Test Organization',
            'designation' => 'Test Team',
            'conference' => 'SEC',
            'abbreviation' => 'TT',
            'color' => '#8c2232',
            'logos' => ['https://example.test/team-logo.png'],
            'social_media' => [['label' => 'X', 'url' => 'https://x.com/test-team']],
            'type' => TeamType::COLLEGE->value,
            'sports' => [Sport::BASKETBALL->value],
        ];

        // ensure team does not exist
        $this->assertDatabaseMissing('teams', ['designation' => $data['designation']]);

        // try to create the team
        $team = $this->service->create(ValidatedTeamData::fromArray($data));

        // verify team exists in database
        $this->assertDatabaseHas('teams', ['designation' => $data['designation']]);

        expect($team)->toBeInstanceOf(Team::class);
        expect($team->organization)->toBe($data['organization']);
        expect($team->designation)->toBe($data['designation']);
        expect($team->sports->pluck('conference')->toArray())->toBe([$data['conference']]);
        expect($team->abbreviation)->toBe($data['abbreviation']);
        expect($team->color)->toBe($data['color']);
        expect($team->logos)->toBe($data['logos']);
        expect($team->social_media)->toBe($data['social_media']);
        expect($team->type)->toBe($data['type']);
        expect($team->sports->pluck('sport')->toArray())->toBe([Sport::BASKETBALL]);
        expect(Str::isUlid((string) $team->ulid))->toBeTrue();
    });
});

describe('update a team', function () {
    test('with valid data', function () {
        // create existing team
        $team = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'Old Organization',
            'designation' => 'Old Designation',
            'abbreviation' => 'OD',
            'color' => '#000000',
            'logos' => ['https://example.test/old-logo.png'],
            'social_media' => [['label' => 'X', 'url' => 'https://x.com/old-team']],
            'type' => TeamType::COLLEGE,
        ]);

        // data to update to
        $data = ValidatedTeamData::fromArray([
            'organization' => 'New Organization',
            'designation' => 'New Designation',
            'conference' => 'ACC',
            'abbreviation' => 'ND',
            'color' => '#123456',
            'logos' => ['https://example.test/new-logo.png'],
            'social_media' => [['label' => 'Instagram', 'url' => 'https://instagram.com/new-team']],
            'type' => TeamType::PROFESSIONAL->value,
            'sports' => [Sport::BASKETBALL->value],
        ]);

        // ensure updated team does not exist
        $this->assertDatabaseMissing('teams', [
            'designation' => $data->designation,
        ]);

        // try to update the team
        $updatedTeam = $this->service->update($team, $data);

        // verify updated team exists in database
        $this->assertDatabaseHas('teams', [
            'designation' => $data->designation,
        ]);

        // verify returned team is the same instance
        expect($updatedTeam)->toBe($team);

        // verify updated data
        expect($team->organization)->toBe($data->organization);
        expect($team->designation)->toBe($data->designation);
        expect($team->sports->pluck('conference')->toArray())->toBe([$data->conference]);
        expect($team->abbreviation)->toBe($data->abbreviation);
        expect($team->color)->toBe($data->color);
        expect($team->logos)->toBe($data->logos);
        expect($team->social_media)->toBe($data->socialMedia);
        expect($team->type)->toBe($data->type->value);
        expect($team->sports->pluck('sport')->toArray())->toBe($data->sports);
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
