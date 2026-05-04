<?php

use App\DTO\ImportedTeamData;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;
use App\DTO\ValidatedTeamData;
use App\Exceptions\TeamImportException;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamType;
use App\Services\TeamImportManager;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamImportSourceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function managerImportData(array $options = []): TeamImportData
{
    return new TeamImportData(
        source: 'cfbd',
        options: $options,
    );
}

function importedTeam(array $overrides = []): ImportedTeamData
{
    $data = array_merge([
        'organization' => 'UCLA',
        'sport' => Sport::FOOTBALL->value,
        'conference' => 'Big Ten',
        'type' => TeamType::COLLEGE->value,
        'designation' => 'Bruins',
        'abbreviation' => 'UCLA',
        'color' => '#2774ae',
        'alternateColor' => '#ffd100',
        'logos' => ['https://example.test/ucla.png'],
        'socialMedia' => [['label' => 'X', 'url' => 'https://x.com/ucla']],
    ], $overrides);

    return new ImportedTeamData(
        organization: $data['organization'],
        sport: $data['sport'],
        conference: $data['conference'],
        type: $data['type'],
        designation: $data['designation'],
        abbreviation: $data['abbreviation'],
        color: $data['color'],
        alternateColor: $data['alternateColor'],
        logos: $data['logos'],
        socialMedia: $data['socialMedia'],
    );
}

describe('import', function () {
    test('updates matching teams via preloaded lookup and creates new teams', function () {
        $existingTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'UCLA',
            'conference' => 'Big Ten',
            'type' => TeamType::COLLEGE->value,
        ]);

        $source = \Mockery::mock(TeamImportSourceInterface::class);
        $source->allows('key')->andReturn('cfbd');
        $source->allows('label')->andReturn('CFBD API');
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(
            items: [
                importedTeam(['organization' => 'ucla', 'conference' => 'big ten']),
                importedTeam(['organization' => 'USC', 'conference' => 'Big Ten', 'designation' => 'Trojans', 'abbreviation' => 'USC']),
            ],
            errors: [],
        ));

        $teamCommand = \Mockery::mock(TeamCommandInterface::class);
        $teamCommand
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (Team $team, ValidatedTeamData $dto): bool {
                return $team->organization === 'UCLA' && $dto->designation === 'Bruins';
            })
            ->andReturnUsing(function (Team $team, ValidatedTeamData $dto): Team {
                $team->designation = $dto->designation;
                $team->save();

                return $team;
            });

        $teamCommand
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function () {
                return Team::factory()->withoutSports()->create([
                    'organization' => 'USC',
                    'conference' => 'Big Ten',
                    'type' => TeamType::COLLEGE->value,
                ]);
            });

        $manager = new TeamImportManager($teamCommand, [$source]);

        $result = $manager->import(managerImportData());

        expect($result->source)->toBe('cfbd')
            ->and($result->sourceLabel)->toBe('CFBD API')
            ->and($result->importedCount)->toBe(2)
            ->and($result->errors)->toBe([])
            ->and(Team::query()->where('organization', 'USC')->exists())->toBeTrue();

        $existingTeam->refresh();
        expect($existingTeam->designation)->toBe('Bruins');
    });

    test('uses in-memory lookup to avoid duplicate creates within the same chunk', function () {
        $source = \Mockery::mock(TeamImportSourceInterface::class);
        $source->allows('key')->andReturn('cfbd');
        $source->allows('label')->andReturn('CFBD API');
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(
            items: [
                importedTeam(['organization' => 'Texas', 'conference' => 'SEC']),
                importedTeam(['organization' => 'Texas', 'conference' => 'SEC']),
            ],
            errors: [],
        ));

        $createdTeam = Team::factory()->withoutSports()->create([
            'organization' => 'Texas',
            'conference' => 'SEC',
            'type' => TeamType::COLLEGE->value,
        ]);

        $teamCommand = \Mockery::mock(TeamCommandInterface::class);
        $teamCommand
            ->shouldReceive('create')
            ->once()
            ->andReturn($createdTeam);

        $teamCommand
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (Team $team, ValidatedTeamData $dto): bool {
                return $team->organization === 'Texas';
            })
            ->andReturnUsing(fn (Team $team) => $team);

        $manager = new TeamImportManager($teamCommand, [$source]);

        $result = $manager->import(managerImportData(['chunk_size' => 100]));

        expect($result->importedCount)->toBe(2)
            ->and($result->errors)->toBe([]);
    });

    test('throws when the selected source does not exist', function () {
        $manager = new TeamImportManager(
            teamCommandService: \Mockery::mock(TeamCommandInterface::class),
            sources: [],
        );

        $manager->import(new TeamImportData(source: 'missing', options: []));
    })->throws(TeamImportException::class, 'Selected import source is not available.');
});
