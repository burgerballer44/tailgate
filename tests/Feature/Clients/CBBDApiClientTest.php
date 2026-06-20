<?php

use App\Clients\CBBDApiClient;
use App\Exceptions\GameImportException;
use App\Exceptions\TeamImportException;
use Illuminate\Support\Facades\Http;

function getCBBDApiClient($token = 'test-token'): CBBDApiClient
{
    return new CBBDApiClient(
        token: $token,
        baseUrl: 'https://api.collegebasketballdata.com',
    );
}

describe('fetchGames', function () {
    test('uses the expected provider code', function () {
        $client = getCBBDApiClient();
        $providerCodeMethod = new ReflectionMethod($client, 'providerCode');

        expect($providerCodeMethod->invoke($client))->toBe('CBBD');
    });

    test('throws when token is blank string', function () {
        $client = getCBBDApiClient('');

        iterator_to_array($client->fetchGames(['year' => 2026]), preserve_keys: false);
    })->throws(GameImportException::class, 'CBBD API credentials are not configured.');

    test('throws when token is null', function () {
        $client = getCBBDApiClient(null);

        iterator_to_array($client->fetchGames(['year' => 2026]), preserve_keys: false);
    })->throws(GameImportException::class, 'CBBD API credentials are not configured.');

    test('sends expected request and returns a decoded item stream', function () {
        Http::fake([
            'https://api.collegebasketballdata.com/games*' => Http::response([
                ['id' => 1, 'homeTeam' => 'A', 'awayTeam' => 'B'],
            ], 200),
        ]);

        $client = getCBBDApiClient();

        $result = iterator_to_array($client->fetchGames([
            'year' => 2026,
            'seasonType' => 'regular',
            'conference' => 'ACC',
        ]), preserve_keys: false);

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(1)
            ->and($result[0]['id'])->toBe(1);

        Http::assertSent(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && $request->url() === 'https://api.collegebasketballdata.com/games?year=2026&seasonType=regular&conference=ACC'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->hasHeader('Accept', 'application/json')
                && ($query['year'] ?? null) === '2026'
                && ($query['seasonType'] ?? null) === 'regular'
                && ($query['conference'] ?? null) === 'ACC';
        });
    });

    test('streams decoded payload rows in order', function () {
        Http::fake([
            'https://api.collegebasketballdata.com/games*' => Http::response([
                ['id' => 1, 'homeTeam' => 'A', 'awayTeam' => 'B'],
                ['id' => 2, 'homeTeam' => 'C', 'awayTeam' => 'D'],
            ], 200),
        ]);

        $client = getCBBDApiClient();

        $result = iterator_to_array(
            $client->fetchGames(['year' => 2026, 'seasonType' => 'regular', 'conference' => 'ACC']),
            preserve_keys: false,
        );

        expect($result)->toHaveCount(2)
            ->and($result[0]['id'])->toBe(1)
            ->and($result[1]['id'])->toBe(2);
    });

    test('maps request exceptions to GameImportException', function () {
        Http::fake([
            'https://api.collegebasketballdata.com/games*' => Http::response(['message' => 'Server error'], 500),
        ]);

        $client = getCBBDApiClient();

        try {
            iterator_to_array($client->fetchGames(['year' => 2026]), preserve_keys: false);

            $this->fail('Expected GameImportException to be thrown.');
        } catch (GameImportException $exception) {
            expect($exception->getMessage())
                ->toContain('CBBD API request failed: HTTP request returned status code 500:')
                ->toContain('{"message":"Server error"}');
        }
    });

    test('throws when games payload is invalid json', function () {
        Http::fake([
            'https://api.collegebasketballdata.com/games*' => Http::response('invalid', 200),
        ]);

        $client = getCBBDApiClient();

        iterator_to_array($client->fetchGames(['year' => 2026]), preserve_keys: false);
    })->throws(GameImportException::class, 'CBBD returned an invalid response payload.');
});

describe('fetchTeams', function () {
    test('throws when token is blank string', function () {
        $client = getCBBDApiClient('');

        iterator_to_array($client->fetchTeams(['year' => 2026]), preserve_keys: false);
    })->throws(TeamImportException::class, 'CBBD API credentials are not configured.');

    test('sends expected request and returns a decoded item stream', function () {
        Http::fake([
            'https://api.collegebasketballdata.com/teams*' => Http::response([
                ['id' => 100, 'school' => 'Duke', 'conference' => 'ACC'],
            ], 200),
        ]);

        $client = getCBBDApiClient();

        $result = iterator_to_array($client->fetchTeams([
            'year' => 2026,
            'conference' => 'ACC',
        ]), preserve_keys: false);

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(1)
            ->and($result[0]['school'])->toBe('Duke');

        Http::assertSent(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && $request->url() === 'https://api.collegebasketballdata.com/teams?year=2026&conference=ACC'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && ($query['year'] ?? null) === '2026'
                && ($query['conference'] ?? null) === 'ACC';
        });
    });

    test('streams decoded payload rows in order', function () {
        Http::fake([
            'https://api.collegebasketballdata.com/teams*' => Http::response([
                ['id' => 100, 'school' => 'Duke', 'conference' => 'ACC'],
                ['id' => 101, 'school' => 'Kansas', 'conference' => 'Big 12'],
            ], 200),
        ]);

        $client = getCBBDApiClient();

        $result = iterator_to_array(
            $client->fetchTeams(['year' => 2026, 'conference' => 'ACC']),
            preserve_keys: false,
        );

        expect($result)->toHaveCount(2)
            ->and($result[0]['id'])->toBe(100)
            ->and($result[1]['id'])->toBe(101);
    });

    test('maps request exceptions to TeamImportException', function () {
        Http::fake([
            'https://api.collegebasketballdata.com/teams*' => Http::response(['message' => 'Server error'], 500),
        ]);

        $client = getCBBDApiClient();

        try {
            iterator_to_array($client->fetchTeams(['year' => 2026]), preserve_keys: false);

            $this->fail('Expected TeamImportException to be thrown.');
        } catch (TeamImportException $exception) {
            expect($exception->getMessage())
                ->toContain('CBBD API request failed: HTTP request returned status code 500:')
                ->toContain('{"message":"Server error"}');
        }
    });

    test('throws when teams payload is invalid json', function () {
        Http::fake([
            'https://api.collegebasketballdata.com/teams*' => Http::response('invalid', 200),
        ]);

        $client = getCBBDApiClient();

        iterator_to_array($client->fetchTeams(['year' => 2026]), preserve_keys: false);
    })->throws(TeamImportException::class, 'CBBD returned an invalid response payload.');
});
