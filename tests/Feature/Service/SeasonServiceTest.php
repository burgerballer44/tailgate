<?php

use App\Models\Game;
use App\Models\Sport;
use App\Models\Season;
use App\Models\SeasonType;
use Illuminate\Support\Str;
use App\Services\GameService;
use App\DTO\ValidatedGameData;
use Illuminate\Support\Carbon;
use App\Services\SeasonService;
use App\DTO\ValidatedSeasonData;

beforeEach(function () {
    $this->service = new SeasonService(new GameService());
});

describe('create a season', function () {
    test('with valid data', function () {
        // create season data
        $data = Season::factory()->make()->toArray();

        // ensure season does not exist
        $this->assertDatabaseMissing('seasons', ['name' => $data['name']]);

        // try to create the season
        $season = $this->service->create(ValidatedSeasonData::fromArray($data));

        // verify season exists in database
        $this->assertDatabaseHas('seasons', ['name' => $data['name']]);

        expect($season)->toBeInstanceOf(Season::class);
        expect($season->name)->toBe($data['name']);
        expect($season->sport)->toBe($data['sport']);
        expect($season->season_type)->toBe($data['season_type']);
        expect($season->season_start)->toBe($data['season_start']);
        expect($season->season_end)->toBe($data['season_end']);
        expect($season->active)->toBe($data['active'] ?? false);
        expect($season->active_date->toDateString())->toBe(Carbon::parse($data['active_date'])->toDateString());
        expect($season->inactive_date->toDateString())->toBe(Carbon::parse($data['inactive_date'])->toDateString());
        expect(Str::isUlid((string)$season->ulid))->toBeTrue();
    });
});

describe('update a season', function () {
    test('with valid data', function () {
        // create existing season
        $season = Season::factory()->create([
            'name' => 'Old Name',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2023-01-01',
            'season_end' => '2023-12-31',
        ]);

        // data to update to
        $data = ValidatedSeasonData::fromArray([
            'name' => 'New Name',
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::POST->value,
            'season_start' => '2024-01-01',
            'season_end' => '2024-12-31',
            'active' => true,
            'active_date' => '2024-01-01',
            'inactive_date' => '2024-12-31',
        ]);

        // ensure updated season does not exist
        $this->assertDatabaseMissing('seasons', [
            'name' => $data->name,
        ]);

        // try to update the season
        $updatedSeason = $this->service->update($season, $data);

        // verify updated season exists in database
        $this->assertDatabaseHas('seasons', [
            'name' => $data->name,
        ]);

        // verify returned season is the same instance
        expect($updatedSeason)->toBe($season);

        // verify updated data
        expect($season->name)->toBe($data->name);
        expect($season->sport)->toBe($data->sport->value);
        expect($season->season_type)->toBe($data->season_type->value);
        expect($season->season_start)->toBe((string) $data->season_start);
        expect($season->season_end)->toBe((string) $data->season_end);
        expect($season->active)->toBe($data->active);
        expect($season->active_date->toDateString())->toBe((string) $data->active_date);
        expect($season->inactive_date->toDateString())->toBe((string) $data->inactive_date);
    });

    test('updates with same values', function () {
        // create existing season
        $season = Season::factory()->create([
            'name' => 'Original Name',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2023-01-01',
            'season_end' => '2023-12-31',
        ]);

        // data to update to with same values
        $data = ValidatedSeasonData::fromArray([
            'name' => 'Original Name',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2023-01-01',
            'season_end' => '2023-12-31',
            'active' => false,
            'active_date' => '2023-01-01',
            'inactive_date' => '2023-12-31',
        ]);

        // try to update the season
        $updatedSeason = $this->service->update($season, $data);

        // verify returned season is the same instance
        expect($updatedSeason)->toBe($season);

        // verify data is unchanged
        expect($updatedSeason->toArray())->toBe($season->toArray());
    });
});

describe('delete', function () {
    test('deletes a season', function () {
        // create a season
        $season = Season::factory()->create();

        // delete the season
        $this->service->delete($season);

        // verify season is deleted from database
        expect(Season::find($season->id))->toBeNull();
    });
});

describe('add a game to a season', function () {
    test('with valid data', function () {
        // create a season
        $season = Season::factory()->create();

        // data for new game
        $data = ValidatedGameData::fromArray(Game::factory()->make([
            'season_id' => $season->id,
        ])->toArray());

        // ensure game does not exist
        $this->assertDatabaseMissing('games', [
            'season_id' => $season->id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
        ]);

        // try to add the game to the season
        $game = $this->service->addGame($season, $data);

        // verify game exists in database
        $this->assertDatabaseHas('games', [
            'season_id' => $season->id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date' => $data->start_date,
            'start_time' => $data->start_time,
        ]);

        expect($game)->toBeInstanceOf(Game::class);
        expect($game->season_id)->toBe($season->id);
        expect($game->home_team_id)->toBe($data->home_team_id);
        expect($game->away_team_id)->toBe($data->away_team_id);
        expect($game->home_team_score)->toBe($data->home_team_score);
        expect($game->away_team_score)->toBe($data->away_team_score);
        expect($game->start_date)->toBe((string) $data->start_date);
        expect($game->start_time)->toBe((string) $data->start_time);
    });
});