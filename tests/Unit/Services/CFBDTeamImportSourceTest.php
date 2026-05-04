<?php

use App\Clients\CFBDApiClient;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;
use App\Exceptions\TeamImportException;
use App\Models\Sport;
use App\Models\TeamType;
use App\Services\ImportSources\CFBDTeamImportSource;

/**
 * @return array{0: CFBDTeamImportSource, 1: \Mockery\MockInterface}
 */
function setupSource(): array
{
    /** @var \Mockery\MockInterface&CFBDApiClient $client */
    $client = \Mockery::mock(CFBDApiClient::class);

    return [new CFBDTeamImportSource($client), $client];
}

function teamImportData(array $options = []): TeamImportData
{
    return new TeamImportData(
        source: 'cfbd',
        options: $options,
    );
}

function sampleTeam(array $overrides = []): array
{
    return array_merge([
        'id' => 2000,
        'school' => 'Abilene Christian',
        'mascot' => 'Wildcats',
        'abbreviation' => 'ACU',
        'alternateNames' => ['Abilene Christian', 'ACU'],
        'conference' => 'UAC',
        'division' => null,
        'classification' => 'fcs',
        'color' => '#592d82',
        'logos' => [
            'https://example.test/acu-primary.png',
            'https://example.test/acu-secondary.png',
        ],
        'twitter' => '@ACUFootball',
        'location' => [
            'city' => 'Abilene',
            'state' => 'TX',
        ],
    ], $overrides);
}

/**
 * @param list<array<string, mixed>> $rows
 * @return \Generator<int, array<string, mixed>>
 */
function teamRowStream(array $rows): \Generator
{
    foreach ($rows as $row) {
        yield $row;
    }
}

/**
 * Collects an ImportFetchStream into plain arrays for assertion.
 *
 * @return array{teams: list<\App\DTO\ImportedTeamData>, errors: list<string>}
 */
function collectStream(ImportFetchStream $stream): array
{
    $teams = iterator_to_array($stream->items(), preserve_keys: false);
    $errors = $stream->errors();

    return compact('teams', 'errors');
}

describe('identity methods', function () {
    test('returns static source metadata', function () {
        [$source] = setupSource();

        expect($source->key())->toBe('cfbd')
            ->and($source->label())->toBe('CFBD API')
            ->and($source->type())->toBe('api')
            ->and($source->description())->toBe('Imports football teams from CollegeFootballData.');
    });
});

describe('fetch', function () {
    test('passes year and conference query options to the client', function () {
        [$source, $client] = setupSource();
        $data = teamImportData(['year' => 2026, 'conference' => 'ACC']);

        $client
            ->shouldReceive('fetchTeams')
            ->once()
            ->with(['year' => 2026, 'conference' => 'ACC'])
            ->andReturn(teamRowStream([]));

        $source->fetch($data);
    });

    test('passes null for year and conference when options are absent', function () {
        [$source, $client] = setupSource();

        $client
            ->shouldReceive('fetchTeams')
            ->once()
            ->with(['year' => null, 'conference' => null])
            ->andReturn(teamRowStream([]));

        $source->fetch(teamImportData());
    });

    test('maps valid CFBD payload rows into imported team DTOs', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam(),
            sampleTeam([
                'id' => 2001,
                'school' => ' Adams State ',
                'mascot' => ' Grizzlies ',
                'abbreviation' => ' ADST ',
                'alternateNames' => ['Adams State', 'ADST'],
                'conference' => ' Rocky Mountain ',
                'classification' => 'ii',
                'color' => '#null',
                'logos' => [
                    'https://example.test/adams-primary.png',
                    'https://example.test/adams-secondary.png',
                ],
                'twitter' => null,
            ]),
        ]));

        ['teams' => $teams, 'errors' => $errors] = collectStream($source->fetch(teamImportData()));

        expect($teams)->toHaveCount(2)
            ->and($errors)->toBe([])
            // first team
            ->and($teams[0]->organization)->toBe('Abilene Christian')
            ->and($teams[0]->sport)->toBe(Sport::FOOTBALL->value)
            ->and($teams[0]->type)->toBe(TeamType::COLLEGE->value)
            ->and($teams[0]->designation)->toBe('ACU')
            ->and($teams[0]->abbreviation)->toBe('ACU')
            ->and($teams[0]->conference)->toBe('UAC')
            ->and($teams[0]->color)->toBe('#592d82')
            ->and($teams[0]->logos)->toBe([
                'https://example.test/acu-primary.png',
                'https://example.test/acu-secondary.png',
            ])
            ->and($teams[0]->socialMedia)->toBe([['label' => 'X', 'url' => 'https://x.com/ACUFootball']])
            // second team – trimming, #null colors, null twitter
            ->and($teams[1]->organization)->toBe('Adams State')
            ->and($teams[1]->sport)->toBe(Sport::FOOTBALL->value)
            ->and($teams[1]->type)->toBe(TeamType::COLLEGE->value)
            ->and($teams[1]->designation)->toBe('ADST')
            ->and($teams[1]->abbreviation)->toBe('ADST')
            ->and($teams[1]->conference)->toBe('Rocky Mountain')
            ->and($teams[1]->color)->toBeNull()
            ->and($teams[1]->socialMedia)->toBeNull();
    });

    test('lowercases hex color values', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam(['color' => '#592D82']),
        ]));

        ['teams' => $teams] = collectStream($source->fetch(teamImportData()));

        expect($teams[0]->color)->toBe('#592d82');
    });

    test('maps missing school values to Unknown organization fallback', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam(['id' => 1, 'school' => null]),
            sampleTeam(['id' => 2, 'school' => '']),
            sampleTeam(['id' => 3, 'school' => '  ']),
            sampleTeam(['id' => 4, 'school' => 'UCLA']),
        ]));

        ['teams' => $teams, 'errors' => $errors] = collectStream($source->fetch(teamImportData()));

        expect($teams)->toHaveCount(2)
            ->and($teams[0]->organization)->toBe('Unknown')
            ->and($teams[1]->organization)->toBe('UCLA')
            ->and($errors)->toHaveCount(2)
            ->and($errors[0])->toContain('CFBD team 2')
            ->and($errors[1])->toContain('CFBD team 3');
    });

    test('normalizes empty optional strings to null', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam([
                'abbreviation' => '',
                'mascot' => ' ',
                'color' => '#null',
                'logos' => ['not-a-url', 'https://example.test/valid-logo.png', 123],
                'twitter' => ' ',
            ]),
        ]));

        ['teams' => $teams] = collectStream($source->fetch(teamImportData()));

        expect($teams[0]->designation)->toBeNull()
            ->and($teams[0]->abbreviation)->toBeNull()
            ->and($teams[0]->color)->toBeNull()
            ->and($teams[0]->logos)->toBe(['https://example.test/valid-logo.png'])
            ->and($teams[0]->socialMedia)->toBeNull();
    });

    test('returns empty result when client returns no teams', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([]));

        ['teams' => $teams, 'errors' => $errors] = collectStream($source->fetch(teamImportData()));

        expect($teams)->toBe([])
            ->and($errors)->toBe([]);
    });

    test('keeps an existing twitter url without rewriting it', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam([
                'twitter' => 'https://x.com/realaccount',
            ]),
        ]));

        ['teams' => $teams] = collectStream($source->fetch(teamImportData()));

        expect($teams[0]->socialMedia)->toBe([
            ['label' => 'X', 'url' => 'https://x.com/realaccount'],
        ]);
    });

    test('sets social media to null when twitter is absent', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam(['twitter' => null]),
        ]));

        ['teams' => $teams] = collectStream($source->fetch(teamImportData()));

        expect($teams[0]->socialMedia)->toBeNull();
    });

    test('sets logos to null when logos field is missing', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam(['logos' => null]),
        ]));

        ['teams' => $teams] = collectStream($source->fetch(teamImportData()));

        expect($teams[0]->logos)->toBeNull();
    });

    test('sets logos to null when all logo entries are invalid urls', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam(['logos' => ['not-a-url', 123, null]]),
        ]));

        ['teams' => $teams] = collectStream($source->fetch(teamImportData()));

        expect($teams[0]->logos)->toBeNull();
    });

    test('maps missing conference values to Unknown conference fallback', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            sampleTeam(['id' => 10, 'conference' => null]),
            sampleTeam(['id' => 11, 'conference' => '']),
            sampleTeam(['id' => 12, 'conference' => '   ']),
            sampleTeam(['id' => 13, 'conference' => 'SEC']),
        ]));

        ['teams' => $teams, 'errors' => $errors] = collectStream($source->fetch(teamImportData()));

        expect($teams)->toHaveCount(2)
            ->and($teams[0]->conference)->toBe('Unknown')
            ->and($teams[1]->conference)->toBe('SEC')
            ->and($errors)->toHaveCount(2)
            ->and($errors[0])->toContain('CFBD team 11')
            ->and($errors[1])->toContain('CFBD team 12');
    });

    test('maps a row without id and school using fallback values without error', function () {
        [$source, $client] = setupSource();
        $client->allows('fetchTeams')->andReturn(teamRowStream([
            ['school' => null, 'conference' => 'SEC'],  // no id key — falls back to teamNumber (1)
        ]));

        ['teams' => $teams, 'errors' => $errors] = collectStream($source->fetch(teamImportData()));

        expect($teams)->toHaveCount(1)
            ->and($teams[0]->organization)->toBe('Unknown')
            ->and($teams[0]->conference)->toBe('SEC')
            ->and($errors)->toBe([]);
    });

    test('propagates TeamImportException thrown by the client', function () {
        [$source, $client] = setupSource();

        $client
            ->allows('fetchTeams')
            ->andThrow(new TeamImportException('CFBD API credentials are not configured.'));

        $source->fetch(teamImportData());
    })->throws(TeamImportException::class, 'CFBD API credentials are not configured.');
});
