<?php

use App\Clients\CFBDApiClient;
use App\Exceptions\GameImportException;
use App\Exceptions\TeamImportException;
use Illuminate\Support\Facades\Http;

function getCFBDApiClient($token = 'test-token'): CFBDApiClient
{
    return new CFBDApiClient(
        token: $token,
        baseUrl: 'https://api.collegefootballdata.com',
    );
}

describe('fetchGames', function () {
	test('throws when token is blank string', function () {
		$client = getCFBDApiClient('');

		iterator_to_array($client->fetchGames(['year' => 2026]), preserve_keys: false);
	})->throws(GameImportException::class, 'CFBD API credentials are not configured.');

	test('throws when token is null', function () {
		$client = getCFBDApiClient(null);

		iterator_to_array($client->fetchGames(['year' => 2026]), preserve_keys: false);
	})->throws(GameImportException::class, 'CFBD API credentials are not configured.');

	test('sends expected request and returns a decoded item stream', function () {
		Http::fake([
			'https://api.collegefootballdata.com/games*' => Http::response([
				['id' => 1, 'homeTeam' => 'A', 'awayTeam' => 'B'],
			], 200),
		]);

		$client = getCFBDApiClient();

		$result = iterator_to_array($client->fetchGames([
			'year' => 2026,
			'seasonType' => 'regular',
			'week' => 1,
		]), preserve_keys: false);

		expect($result)->toBeArray()
			->and($result)->toHaveCount(1)
			->and($result[0]['id'])->toBe(1);

		Http::assertSent(function ($request) {
			parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

			return $request->method() === 'GET'
				&& $request->url() === 'https://api.collegefootballdata.com/games?year=2026&seasonType=regular&week=1'
				&& $request->hasHeader('Authorization', 'Bearer test-token')
				&& $request->hasHeader('Accept', 'application/json')
				&& ($query['year'] ?? null) === '2026'
				&& ($query['seasonType'] ?? null) === 'regular'
				&& ($query['week'] ?? null) === '1';
		});
	});

	test('streams decoded payload rows in order', function () {
		Http::fake([
			'https://api.collegefootballdata.com/games*' => Http::response([
				['id' => 1, 'homeTeam' => 'A', 'awayTeam' => 'B'],
				['id' => 2, 'homeTeam' => 'C', 'awayTeam' => 'D'],
			], 200),
		]);

		$client = getCFBDApiClient();

		$result = iterator_to_array(
			$client->fetchGames(['year' => 2026, 'seasonType' => 'regular', 'week' => 1]),
			preserve_keys: false,
		);

		expect($result)->toHaveCount(2)
			->and($result[0]['id'])->toBe(1)
			->and($result[1]['id'])->toBe(2);
	});

	test('maps request exceptions to GameImportException', function () {
		Http::fake([
			'https://api.collegefootballdata.com/games*' => Http::response(['message' => 'Server error'], 500),
		]);

		$client = getCFBDApiClient();

		iterator_to_array($client->fetchGames(['year' => 2026]), preserve_keys: false);
	})->throws(GameImportException::class, 'CFBD API request failed:');

	test('throws when games payload is invalid json', function () {
		Http::fake([
			'https://api.collegefootballdata.com/games*' => Http::response('invalid', 200),
		]);

		$client = getCFBDApiClient();

		iterator_to_array($client->fetchGames(['year' => 2026]), preserve_keys: false);
	})->throws(GameImportException::class, 'CFBD returned an invalid response payload.');
});

describe('fetchTeams', function () {
	test('throws when token is blank string', function () {
		$client = getCFBDApiClient('');

		iterator_to_array($client->fetchTeams(['year' => 2026]), preserve_keys: false);
	})->throws(TeamImportException::class, 'CFBD API credentials are not configured.');

	test('sends expected request and returns a decoded item stream', function () {
		Http::fake([
			'https://api.collegefootballdata.com/teams*' => Http::response([
				['id' => 2000, 'school' => 'Abilene Christian', 'conference' => 'UAC'],
			], 200),
		]);

		$client = getCFBDApiClient();

		$result = iterator_to_array($client->fetchTeams([
			'year' => 2026,
			'conference' => 'UAC',
		]), preserve_keys: false);

		expect($result)->toBeArray()
			->and($result)->toHaveCount(1)
			->and($result[0]['school'])->toBe('Abilene Christian');

		Http::assertSent(function ($request) {
			parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

			return $request->method() === 'GET'
				&& $request->url() === 'https://api.collegefootballdata.com/teams?year=2026&conference=UAC'
				&& $request->hasHeader('Authorization', 'Bearer test-token')
				&& ($query['year'] ?? null) === '2026'
				&& ($query['conference'] ?? null) === 'UAC';
		});
	});

	test('streams decoded payload rows in order', function () {
		Http::fake([
			'https://api.collegefootballdata.com/teams*' => Http::response([
				['id' => 2000, 'school' => 'Abilene Christian', 'conference' => 'UAC'],
				['id' => 2001, 'school' => 'Adams State', 'conference' => 'RMAC'],
			], 200),
		]);

		$client = getCFBDApiClient();

		$result = iterator_to_array(
			$client->fetchTeams(['year' => 2026, 'conference' => 'UAC']),
			preserve_keys: false,
		);

		expect($result)->toHaveCount(2)
			->and($result[0]['id'])->toBe(2000)
			->and($result[1]['id'])->toBe(2001);
	});

	test('maps request exceptions to TeamImportException', function () {
		Http::fake([
			'https://api.collegefootballdata.com/teams*' => Http::response(['message' => 'Server error'], 500),
		]);

		$client = getCFBDApiClient();

		iterator_to_array($client->fetchTeams(['year' => 2026]), preserve_keys: false);
	})->throws(TeamImportException::class, 'CFBD API request failed:');

	test('throws when teams payload is invalid json', function () {
		Http::fake([
			'https://api.collegefootballdata.com/teams*' => Http::response('invalid', 200),
		]);

		$client = getCFBDApiClient();

		iterator_to_array($client->fetchTeams(['year' => 2026]), preserve_keys: false);
	})->throws(TeamImportException::class, 'CFBD returned an invalid response payload.');
});
