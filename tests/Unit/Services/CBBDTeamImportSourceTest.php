<?php

use App\Clients\CBBDApiClient;
use App\DTO\ImportedTeamData;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;
use App\Exceptions\TeamImportException;
use App\Models\Sport;
use App\Models\TeamType;
use App\Services\ImportSources\CBBDTeamImportSource;
use Mockery\MockInterface;

/**
 * @return array{0: CBBDTeamImportSource, 1: MockInterface}
 */
function setupCBBDSource(): array
{
    /** @var MockInterface&CBBDApiClient $client */
    $client = Mockery::mock(CBBDApiClient::class);

    return [new CBBDTeamImportSource($client), $client];
}

function cbbdTeamImportData(array $options = []): TeamImportData
{
    return new TeamImportData(
        source: 'cbbd',
        options: $options,
    );
}

function cbbdSampleTeam(array $overrides = []): array
{
    return array_merge([
        'id' => 100,
        'school' => 'Duke',
        'mascot' => 'Blue Devils',
        'abbreviation' => 'DUKE',
        'conference' => 'ACC',
        'color' => '#001a57',
        'logos' => [
            'https://example.test/duke-primary.png',
            'https://example.test/duke-secondary.png',
        ],
        'twitter' => '@DukeMBB',
    ], $overrides);
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return Generator<int, array<string, mixed>>
 */
function cbbdTeamRowStream(array $rows): Generator
{
    foreach ($rows as $row) {
        yield $row;
    }
}

/**
 * @return array{teams: list<ImportedTeamData>, errors: list<string>}
 */
function collectCBBDTeamStream(ImportFetchStream $stream): array
{
    $teams = iterator_to_array($stream->items(), preserve_keys: false);
    $errors = $stream->errors();

    return compact('teams', 'errors');
}

describe('identity methods', function () {
    test('returns static source metadata', function () {
        [$source] = setupCBBDSource();

        expect($source->key())->toBe('cbbd')
            ->and($source->label())->toBe('CBBD API')
            ->and($source->type())->toBe('api')
            ->and($source->description())->toBe('Imports basketball teams from CollegeBasketballData.');
    });
});

describe('fetch', function () {
    test('passes year and conference query options to the client', function () {
        [$source, $client] = setupCBBDSource();
        $data = cbbdTeamImportData(['year' => 2026, 'conference' => 'Big East']);

        $client
            ->shouldReceive('fetchTeams')
            ->once()
            ->with(['year' => 2026, 'conference' => 'Big East'])
            ->andReturn(cbbdTeamRowStream([]));

        $source->fetch($data);
    });

    test('passes null for year and conference when options are absent', function () {
        [$source, $client] = setupCBBDSource();

        $client
            ->shouldReceive('fetchTeams')
            ->once()
            ->with(['year' => null, 'conference' => null])
            ->andReturn(cbbdTeamRowStream([]));

        $source->fetch(cbbdTeamImportData());
    });

    test('maps valid payload rows into imported team DTOs', function () {
        [$source, $client] = setupCBBDSource();
        $client->allows('fetchTeams')->andReturn(cbbdTeamRowStream([
            cbbdSampleTeam(),
            cbbdSampleTeam([
                'id' => 101,
                'school' => '  Villanova  ',
                'mascot' => '  Wildcats  ',
                'abbreviation' => '  NOVA  ',
                'conference' => '  Big East  ',
                'color' => '#null',
                'twitter' => null,
            ]),
        ]));

        ['teams' => $teams, 'errors' => $errors] = collectCBBDTeamStream($source->fetch(cbbdTeamImportData()));

        expect($teams)->toHaveCount(2)
            ->and($errors)->toBe([])
            ->and($teams[0]->organization)->toBe('Duke')
            ->and($teams[0]->sport)->toBe(Sport::BASKETBALL->value)
            ->and($teams[0]->type)->toBe(TeamType::COLLEGE->value)
            ->and($teams[0]->designation)->toBe('Blue Devils')
            ->and($teams[0]->abbreviation)->toBe('DUKE')
            ->and($teams[0]->conference)->toBe('ACC')
            ->and($teams[0]->logos)->toBe([
                'https://example.test/duke-primary.png',
                'https://example.test/duke-secondary.png',
            ])
            ->and($teams[0]->socialMedia)->toBe([
                ['label' => 'X', 'url' => 'https://x.com/DukeMBB'],
            ])
            ->and($teams[1]->organization)->toBe('Villanova')
            ->and($teams[1]->designation)->toBe('Wildcats')
            ->and($teams[1]->abbreviation)->toBe('NOVA')
            ->and($teams[1]->conference)->toBe('Big East')
            ->and($teams[1]->color)->toBeNull()
            ->and($teams[1]->socialMedia)->toBeNull();
    });

    test('supports organization fallback keys team and name', function () {
        [$source, $client] = setupCBBDSource();
        $client->allows('fetchTeams')->andReturn(cbbdTeamRowStream([
            [
                'id' => 1,
                'team' => 'Gonzaga',
                'conference' => 'WCC',
            ],
            [
                'id' => 2,
                'name' => 'Arizona',
                'conference' => 'Big 12',
            ],
        ]));

        ['teams' => $teams] = collectCBBDTeamStream($source->fetch(cbbdTeamImportData()));

        expect($teams)->toHaveCount(2)
            ->and($teams[0]->organization)->toBe('Gonzaga')
            ->and($teams[1]->organization)->toBe('Arizona');
    });

    test('supports logos provided as a single string url', function () {
        [$source, $client] = setupCBBDSource();
        $client->allows('fetchTeams')->andReturn(cbbdTeamRowStream([
            cbbdSampleTeam(['logos' => 'https://example.test/single-logo.png']),
        ]));

        ['teams' => $teams] = collectCBBDTeamStream($source->fetch(cbbdTeamImportData()));

        expect($teams[0]->logos)->toBe(['https://example.test/single-logo.png']);
    });

    test('normalizes empty optional strings to null', function () {
        [$source, $client] = setupCBBDSource();
        $client->allows('fetchTeams')->andReturn(cbbdTeamRowStream([
            cbbdSampleTeam([
                'abbreviation' => '',
                'mascot' => ' ',
                'color' => '#null',
                'logos' => ['not-a-url', 'https://example.test/valid-logo.png', 123],
                'twitter' => ' ',
            ]),
        ]));

        ['teams' => $teams] = collectCBBDTeamStream($source->fetch(cbbdTeamImportData()));

        expect($teams[0]->designation)->toBeNull()
            ->and($teams[0]->abbreviation)->toBeNull()
            ->and($teams[0]->color)->toBeNull()
            ->and($teams[0]->logos)->toBe(['https://example.test/valid-logo.png'])
            ->and($teams[0]->socialMedia)->toBeNull();
    });

    test('maps missing organization and conference values to fallback values', function () {
        [$source, $client] = setupCBBDSource();
        $client->allows('fetchTeams')->andReturn(cbbdTeamRowStream([
            cbbdSampleTeam(['id' => 1, 'school' => null]),
            cbbdSampleTeam(['id' => 2, 'conference' => '']),
            cbbdSampleTeam(['id' => 3, 'school' => 'UCLA', 'conference' => 'Big Ten']),
        ]));

        ['teams' => $teams, 'errors' => $errors] = collectCBBDTeamStream($source->fetch(cbbdTeamImportData()));

        expect($teams)->toHaveCount(2)
            ->and($teams[0]->organization)->toBe('Unknown')
            ->and($teams[0]->conference)->toBe('ACC')
            ->and($teams[1]->organization)->toBe('UCLA')
            ->and($teams[1]->conference)->toBe('Big Ten')
            ->and($errors)->toHaveCount(1)
            ->and($errors[0])->toContain('CBBD team 2');
    });

    test('maps a row without id and school using fallback values without error', function () {
        [$source, $client] = setupCBBDSource();
        $client->allows('fetchTeams')->andReturn(cbbdTeamRowStream([
            ['school' => null, 'conference' => 'SEC'],
        ]));

        ['teams' => $teams, 'errors' => $errors] = collectCBBDTeamStream($source->fetch(cbbdTeamImportData()));

        expect($teams)->toHaveCount(1)
            ->and($teams[0]->organization)->toBe('Unknown')
            ->and($teams[0]->conference)->toBe('SEC')
            ->and($errors)->toBe([]);
    });

    test('propagates TeamImportException thrown by the client', function () {
        [$source, $client] = setupCBBDSource();

        $client
            ->allows('fetchTeams')
            ->andThrow(new TeamImportException('CBBD API credentials are not configured.'));

        $source->fetch(cbbdTeamImportData());
    })->throws(TeamImportException::class, 'CBBD API credentials are not configured.');
});
