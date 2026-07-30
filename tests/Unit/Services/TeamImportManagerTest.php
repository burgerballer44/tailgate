<?php

use App\DTO\ImportedTeamData;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;
use App\DTO\ValidatedTeamData;
use App\Exceptions\TeamImportException;
use App\Models\Enums\Sport;
use App\Models\Enums\TeamType;
use App\Models\Team;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamImportSourceInterface;
use App\Services\TeamImportManager;
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
        logos: $data['logos'],
        socialMedia: $data['socialMedia'],
    );
}

describe('import', function () {
    test('updates matching teams via preloaded lookup and creates new teams', function () {
        $existingTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'UCLA',
            'type' => TeamType::COLLEGE->value,
        ]);

        $source = Mockery::mock(TeamImportSourceInterface::class);
        $source->allows('key')->andReturn('cfbd');
        $source->allows('label')->andReturn('CFBD API');
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(
            items: [
                importedTeam(['organization' => 'ucla', 'conference' => 'big ten']),
                importedTeam(['organization' => 'USC', 'conference' => 'Big Ten', 'designation' => 'Trojans', 'abbreviation' => 'USC']),
            ],
            errors: [],
        ));

        $teamCommand = Mockery::mock(TeamCommandInterface::class);
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
                    'type' => TeamType::COLLEGE->value,
                ]);
            });

        $manager = new TeamImportManager($teamCommand, [$source]);

        $result = $manager->import(managerImportData());

        expect($result->source)->toBe('cfbd')
            ->and($result->sourceLabel)->toBe('CFBD API')
            ->and($result->importedCount)->toBe(1)
            ->and($result->updatedCount)->toBe(1)
            ->and($result->errors)->toBe([])
            ->and(Team::query()->where('organization', 'USC')->exists())->toBeTrue();

        $existingTeam->refresh();
        expect($existingTeam->designation)->toBe('Bruins');
    });

    test('uses in-memory lookup to avoid duplicate creates within the same chunk', function () {
        $source = Mockery::mock(TeamImportSourceInterface::class);
        $source->allows('key')->andReturn('cfbd');
        $source->allows('label')->andReturn('CFBD API');
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(
            items: [
                importedTeam(['organization' => 'Texas', 'conference' => 'SEC']),
                importedTeam(['organization' => 'Texas', 'conference' => 'SEC']),
            ],
            errors: [],
        ));

        $createdTeam = Team::factory()->withoutSports()->make([
            'organization' => 'Texas',
            'type' => TeamType::COLLEGE->value,
        ]);

        $teamCommand = Mockery::mock(TeamCommandInterface::class);
        $teamCommand
            ->shouldReceive('create')
            ->once()
            ->andReturn($createdTeam);

        $teamCommand
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (Team $team, ValidatedTeamData $dto): bool {
                return $team->organization === 'Texas' && $dto->organization === 'Texas';
            })
            ->andReturnUsing(fn (Team $team) => $team);

        $manager = new TeamImportManager($teamCommand, [$source]);

        $result = $manager->import(managerImportData(['chunk_size' => 100]));

        expect($result->importedCount)->toBe(1)
            ->and($result->updatedCount)->toBe(1)
            ->and($result->errors)->toBe([]);
    });

    test('merges existing sports, logos and social links during update without clearing missing fields', function () {
        $existingTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => 'UCLA',
            'designation' => 'Bruins',
            'abbreviation' => 'UCLA',
            'color' => '#123456',
            'logos' => ['https://example.test/logo-football.png'],
            'social_media' => [['label' => 'X', 'url' => 'https://x.com/ucla']],
            'type' => TeamType::COLLEGE->value,
        ]);

        $source = Mockery::mock(TeamImportSourceInterface::class);
        $source->allows('key')->andReturn('cfbd');
        $source->allows('label')->andReturn('CFBD API');
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(
            items: [
                importedTeam([
                    'organization' => 'ucla',
                    'conference' => 'big ten',
                    'sport' => Sport::BASKETBALL->value,
                    'abbreviation' => null,
                    'color' => null,
                    'logos' => ['https://example.test/logo-basketball.png'],
                    'socialMedia' => [['label' => 'Instagram', 'url' => 'https://instagram.com/ucla']],
                ]),
            ],
            errors: [],
        ));

        $teamCommand = Mockery::mock(TeamCommandInterface::class);
        $teamCommand
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (Team $team, ValidatedTeamData $dto) use ($existingTeam): bool {
                $sportValues = array_map(fn (Sport $sport): string => $sport->value, $dto->sports);
                sort($sportValues);

                $logos = $dto->logos ?? [];
                sort($logos);

                return $team->is($existingTeam)
                    && $dto->abbreviation === 'UCLA'
                    && $dto->color === '#123456'
                    && $sportValues === [Sport::BASKETBALL->value, Sport::FOOTBALL->value]
                    && $logos === ['https://example.test/logo-basketball.png', 'https://example.test/logo-football.png']
                    && $dto->socialMedia === [
                        ['label' => 'X', 'url' => 'https://x.com/ucla'],
                        ['label' => 'Instagram', 'url' => 'https://instagram.com/ucla'],
                    ];
            })
            ->andReturnUsing(fn (Team $team) => $team);

        $teamCommand->shouldReceive('create')->never();

        $manager = new TeamImportManager($teamCommand, [$source]);

        $result = $manager->import(managerImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->updatedCount)->toBe(1)
            ->and($result->errors)->toBe([]);
    });

    test('merges one team identity across multiple sports with different conferences', function () {
        $existingTeam = Team::factory()->withSports([
            Sport::FOOTBALL->value => 'American Athletic',
        ])->create([
            'organization' => 'Navy',
            'designation' => 'Midshipmen',
            'abbreviation' => 'NAVY',
            'type' => TeamType::COLLEGE->value,
        ]);

        $source = Mockery::mock(TeamImportSourceInterface::class);
        $source->allows('key')->andReturn('cfbd');
        $source->allows('label')->andReturn('CFBD API');
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(
            items: [
                importedTeam([
                    'organization' => 'Navy',
                    'designation' => 'Midshipmen',
                    'sport' => Sport::BASKETBALL->value,
                    'conference' => 'Patriot',
                    'abbreviation' => 'NAVY',
                ]),
            ],
            errors: [],
        ));

        $teamCommand = Mockery::mock(TeamCommandInterface::class);
        $teamCommand
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (Team $team, ValidatedTeamData $dto) use ($existingTeam): bool {
                $sportValues = array_map(fn (Sport $sport): string => $sport->value, $dto->sports);
                sort($sportValues);

                return $team->is($existingTeam)
                    && $sportValues === [Sport::BASKETBALL->value, Sport::FOOTBALL->value]
                    && $dto->sportConferences === [
                        Sport::FOOTBALL->value => 'American Athletic',
                        Sport::BASKETBALL->value => 'Patriot',
                    ];
            })
            ->andReturnUsing(fn (Team $team) => $team);

        $teamCommand->shouldReceive('create')->never();

        $manager = new TeamImportManager($teamCommand, [$source]);

        $result = $manager->import(managerImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->updatedCount)->toBe(1)
            ->and($result->errors)->toBe([]);
    });

    test('throws when the selected source does not exist', function () {
        $manager = new TeamImportManager(
            teamCommandService: Mockery::mock(TeamCommandInterface::class),
            sources: [],
        );

        $manager->import(new TeamImportData(source: 'missing', options: []));
    })->throws(TeamImportException::class, 'Selected import source is not available.');
});
