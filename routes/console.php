<?php

use App\Models\Enums\GroupRole;
use App\Models\Enums\GroupThresholdRule;
use App\Models\Enums\MemberStatus;
use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
use App\Models\Enums\UserRole;
use App\Models\Enums\UserStatus;
use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupSeasonFollow;
use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('tailgate:seed-debug-predictions {--seed=20260712}', function () {
    $seed = (int) $this->option('seed');
    mt_srand($seed);
    $today = CarbonImmutable::today();

    $runToken = strtolower(Str::random(8));

    $developerEmail = env('DEVELOPER_EMAIL', 'developer@example.com');
    $developerName = env('DEVELOPER_NAME', 'Developer User');

    $footballStartDate = $today->startOfWeek(CarbonImmutable::SUNDAY)->subWeeks(4);
    $basketballStartDate = $footballStartDate->addWeeks(3);
    $memberJoinDate = $footballStartDate->subDays(2)->setTime(9, 0);
    $historicalFootballStartDate = $today->subYear()->startOfWeek(CarbonImmutable::SUNDAY)->subWeeks(6);
    $historicalMemberJoinDate = $historicalFootballStartDate->subDays(10)->setTime(9, 0);
    $historicalMemberLeftDate = $historicalFootballStartDate->addWeeks(8)->setTime(0, 0);

    $developerUser = User::query()->where('email', $developerEmail)->first();

    if (! $developerUser instanceof User) {
        $developerUser = User::factory()->create([
            'name' => $developerName,
            'email' => $developerEmail,
            'email_verified_at' => now(),
            'password' => Hash::make(env('DEVELOPER_PASSWORD', 'password')),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::DEVELOPER->value,
        ]);

        $this->warn('Developer user from seeder was missing. Created a developer user for this run.');
    }

    $inviteCode = null;
    do {
        $inviteCode = Str::upper(Str::random(GroupThresholdRule::INVITE_CODE_LENGTH->value()));
    } while (Group::query()->where('invite_code', $inviteCode)->exists());

    $group = Group::factory()->create([
        'name' => 'Test Results Load Group',
        'owner_id' => $developerUser->id,
        'member_limit' => 40,
        'player_limit' => 1,
        'follow_limit' => 4,
        'invite_code' => $inviteCode,
    ]);

    $footballSeason = Season::factory()->active()->create([
        'name' => 'Test Football Season',
        'sport' => Sport::FOOTBALL->value,
        'season_type' => SeasonType::REGULAR->value,
    ]);

    $basketballSeason = Season::factory()->active()->create([
        'name' => 'Test Basketball Season',
        'sport' => Sport::BASKETBALL->value,
        'season_type' => SeasonType::REGULAR->value,
    ]);

    $historicalFootballSeason = Season::factory()->create([
        'name' => 'Test Historical Football Season',
        'sport' => Sport::FOOTBALL->value,
        'season_type' => SeasonType::REGULAR->value,
        'active' => false,
    ]);

    $footballFollowedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
        'organization' => 'Test Football Followed',
        'designation' => 'Team',
        'abbreviation' => 'TESTF',
    ]);

    $basketballFollowedTeam = Team::factory()->withSports([Sport::BASKETBALL])->create([
        'organization' => 'Test Basketball Followed',
        'designation' => 'Team',
        'abbreviation' => 'TESTB',
    ]);

    $historicalFootballFollowedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create([
        'organization' => 'Test Historical Football Followed',
        'designation' => 'Team',
        'abbreviation' => 'THISTF',
    ]);

    $footballOpponents = collect(range(1, 10))->map(function (int $index) {
        return Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => sprintf('Test Football Opponent %02d', $index),
            'designation' => 'Team',
            'abbreviation' => sprintf('TF%02d', $index),
        ]);
    })->values();

    $basketballOpponents = collect(range(1, 12))->map(function (int $index) {
        return Team::factory()->withSports([Sport::BASKETBALL])->create([
            'organization' => sprintf('Test Basketball Opponent %02d', $index),
            'designation' => 'Team',
            'abbreviation' => sprintf('TB%02d', $index),
        ]);
    })->values();

    $historicalFootballOpponents = collect(range(1, 12))->map(function (int $index) {
        return Team::factory()->withSports([Sport::FOOTBALL])->create([
            'organization' => sprintf('Test Historical Football Opponent %02d', $index),
            'designation' => 'Team',
            'abbreviation' => sprintf('THF%02d', $index),
        ]);
    })->values();

    GroupSeasonFollow::query()->create([
        'group_id' => $group->id,
        'season_id' => $footballSeason->id,
    ]);

    GroupSeasonFollow::query()->create([
        'group_id' => $group->id,
        'season_id' => $basketballSeason->id,
    ]);

    GroupSeasonFollow::query()->create([
        'group_id' => $group->id,
        'season_id' => $historicalFootballSeason->id,
    ]);

    Follow::query()->create([
        'group_id' => $group->id,
        'team_id' => $footballFollowedTeam->id,
    ]);

    Follow::query()->create([
        'group_id' => $group->id,
        'team_id' => $basketballFollowedTeam->id,
    ]);

    Follow::query()->create([
        'group_id' => $group->id,
        'team_id' => $historicalFootballFollowedTeam->id,
    ]);

    $ownerMember = Member::query()
        ->where('group_id', $group->id)
        ->where('user_id', $developerUser->id)
        ->firstOrFail();

    Member::query()
        ->where('id', $ownerMember->id)
        ->update([
            'created_at' => $memberJoinDate->toDateTimeString(),
            'updated_at' => $memberJoinDate->toDateTimeString(),
        ]);

    $ownerMember->update([
        'role' => GroupRole::GROUP_ADMIN->value,
        'status' => MemberStatus::APPROVED->value,
    ]);

    $players = collect();

    $players->push(Player::factory()->create([
        'member_id' => $ownerMember->id,
        'player_name' => 'Test Player DEV',
    ]));

    foreach (range(1, 24) as $index) {
        $user = User::factory()->create([
            'name' => sprintf('Test Member %02d', $index),
            'email' => sprintf('test-member-%02d-%s@example.test', $index, $runToken),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
            'email_verified_at' => now(),
        ]);

        $member = Member::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => MemberStatus::APPROVED->value,
            'created_at' => $memberJoinDate->toDateTimeString(),
            'updated_at' => $memberJoinDate->toDateTimeString(),
        ]);

        $players->push(Player::factory()->create([
            'member_id' => $member->id,
            'player_name' => sprintf('Test Player %02d', $index),
        ]));
    }

    $historicalPlayers = collect();
    foreach (range(1, 4) as $index) {
        $historicalStatus = match ($index) {
            2, 4 => MemberStatus::LEFT,
            3 => MemberStatus::REMOVED,
            default => MemberStatus::APPROVED,
        };

        $historicalUser = User::factory()->create([
            'name' => sprintf('Test Historical Member %02d', $index),
            'email' => sprintf('test-historical-member-%02d-%s@example.test', $index, $runToken),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
            'email_verified_at' => now(),
        ]);

        $historicalMemberData = [
            'group_id' => $group->id,
            'user_id' => $historicalUser->id,
            'role' => GroupRole::GROUP_MEMBER->value,
            'status' => $historicalStatus->value,
            'created_at' => $historicalMemberJoinDate->toDateTimeString(),
            'updated_at' => $historicalMemberJoinDate->toDateTimeString(),
        ];

        if (Schema::hasColumn('members', 'left_at') && in_array($historicalStatus, [MemberStatus::LEFT, MemberStatus::REMOVED], true)) {
            $historicalMemberData['left_at'] = $historicalMemberLeftDate->addDays($index)->toDateTimeString();
            $historicalMemberData['updated_at'] = $historicalMemberData['left_at'];
        }

        $historicalMember = Member::factory()->create($historicalMemberData);

        $historicalPlayers->push(Player::factory()->create([
            'member_id' => $historicalMember->id,
            'player_name' => sprintf('Test Historical Player %02d', $index),
        ]));
    }

    $footballGames = collect();
    foreach (range(0, 9) as $index) {
        $gameDate = $footballStartDate->addWeeks($index)->setTime(13, 0);
        $hasOccurred = $gameDate->lessThanOrEqualTo($today->endOfDay());
        $followedIsHome = $index % 2 === 0;
        $opponent = $footballOpponents[$index % $footballOpponents->count()];

        $homeScore = $hasOccurred ? (string) mt_rand(14, 45) : 'TBD';
        $awayScore = $hasOccurred ? (string) mt_rand(7, 42) : 'TBD';

        $footballGames->push(Game::query()->create([
            'season_id' => $footballSeason->id,
            'home_team_id' => $followedIsHome ? $footballFollowedTeam->id : $opponent->id,
            'away_team_id' => $followedIsHome ? $opponent->id : $footballFollowedTeam->id,
            'home_team_score' => $homeScore,
            'away_team_score' => $awayScore,
            'start_date_time' => $gameDate->toDateTimeString(),
            'start_time_tbd' => false,
        ]));
    }

    $basketballDates = collect();
    $weekOffset = 0;
    while ($basketballDates->count() < 25) {
        $daysInWeek = $weekOffset % 2 === 0 ? [2, 4, 6] : [2, 5];

        foreach ($daysInWeek as $dayOffset) {
            if ($basketballDates->count() >= 25) {
                break;
            }

            $basketballDates->push(
                $basketballStartDate
                    ->startOfWeek(CarbonImmutable::SUNDAY)
                    ->addWeeks($weekOffset)
                    ->addDays($dayOffset)
                    ->setTime(19, 0)
            );
        }

        $weekOffset++;
    }

    $basketballGames = collect();
    foreach ($basketballDates as $index => $gameDate) {
        $hasOccurred = $gameDate->lessThanOrEqualTo($today->endOfDay());
        $followedIsHome = $index % 2 === 0;
        $opponent = $basketballOpponents[$index % $basketballOpponents->count()];

        $homeScore = $hasOccurred ? (string) mt_rand(72, 121) : 'TBD';
        $awayScore = $hasOccurred ? (string) mt_rand(68, 118) : 'TBD';

        $basketballGames->push(Game::query()->create([
            'season_id' => $basketballSeason->id,
            'home_team_id' => $followedIsHome ? $basketballFollowedTeam->id : $opponent->id,
            'away_team_id' => $followedIsHome ? $opponent->id : $basketballFollowedTeam->id,
            'home_team_score' => $homeScore,
            'away_team_score' => $awayScore,
            'start_date_time' => $gameDate->toDateTimeString(),
            'start_time_tbd' => false,
        ]));
    }

    $historicalFootballGames = collect();
    foreach (range(0, 11) as $index) {
        $gameDate = $historicalFootballStartDate->addWeeks($index)->setTime(13, 0);
        $followedIsHome = $index % 2 === 0;
        $opponent = $historicalFootballOpponents[$index % $historicalFootballOpponents->count()];

        $historicalFootballGames->push(Game::query()->create([
            'season_id' => $historicalFootballSeason->id,
            'home_team_id' => $followedIsHome ? $historicalFootballFollowedTeam->id : $opponent->id,
            'away_team_id' => $followedIsHome ? $opponent->id : $historicalFootballFollowedTeam->id,
            'home_team_score' => (string) mt_rand(14, 45),
            'away_team_score' => (string) mt_rand(7, 42),
            'start_date_time' => $gameDate->toDateTimeString(),
            'start_time_tbd' => false,
        ]));
    }

    $occurredGames = $footballGames
        ->merge($basketballGames)
        ->filter(fn (Game $game): bool => is_numeric($game->home_team_score) && is_numeric($game->away_team_score))
        ->values();

    $seedDemoPrediction = function (Player $player, Game $game): bool {
        $prediction = Prediction::query()->updateOrCreate(
            [
                'player_id' => $player->id,
                'game_id' => $game->id,
            ],
            [
                'home_team_prediction' => (string) max(0, (int) $game->home_team_score + 3),
                'away_team_prediction' => (string) max(0, (int) $game->away_team_score - 2),
            ]
        );

        return (bool) $prediction->wasRecentlyCreated;
    };

    $createdPredictions = 0;

    $footballDemoGame = $occurredGames->first(fn (Game $game): bool => $game->season_id === $footballSeason->id);
    if ($footballDemoGame instanceof Game) {
        $createdPredictions += $seedDemoPrediction($players->first(), $footballDemoGame) ? 1 : 0;
    }

    $basketballDemoGame = $occurredGames->first(fn (Game $game): bool => $game->season_id === $basketballSeason->id);
    if ($basketballDemoGame instanceof Game) {
        $createdPredictions += $seedDemoPrediction($players->first(), $basketballDemoGame) ? 1 : 0;
    }

    $historicalDemoGame = $historicalFootballGames->first();
    if ($historicalDemoGame instanceof Game) {
        $createdPredictions += $seedDemoPrediction($players->first(), $historicalDemoGame) ? 1 : 0;
    }

    foreach ($occurredGames as $game) {
        $isBasketballGame = $game->season_id === $basketballSeason->id;
        $actualHome = (int) $game->home_team_score;
        $actualAway = (int) $game->away_team_score;

        foreach ($players as $player) {
            // Simulate real-world misses: most players submit, some skip occasionally.
            if (mt_rand(1, 100) > 88) {
                continue;
            }

            $homePrediction = max(
                0,
                $actualHome + ($isBasketballGame ? mt_rand(-18, 18) : mt_rand(-14, 14))
            );
            $awayPrediction = max(
                0,
                $actualAway + ($isBasketballGame ? mt_rand(-18, 18) : mt_rand(-14, 14))
            );

            $prediction = Prediction::query()->updateOrCreate([
                'player_id' => $player->id,
                'game_id' => $game->id,
            ], [
                'home_team_prediction' => (string) $homePrediction,
                'away_team_prediction' => (string) $awayPrediction,
            ]);

            if ($prediction->wasRecentlyCreated) {
                $createdPredictions++;
            }
        }
    }

    $historicalPredictionPool = $players
        ->take(1)
        ->merge($players->shuffle()->take(11))
        ->merge($historicalPlayers)
        ->unique('id')
        ->values();

    $historicalPredictionsCreated = 0;
    foreach ($historicalFootballGames as $game) {
        $actualHome = (int) $game->home_team_score;
        $actualAway = (int) $game->away_team_score;

        foreach ($historicalPredictionPool as $player) {
            if (mt_rand(1, 100) > 84) {
                continue;
            }

            $homePrediction = max(0, $actualHome + mt_rand(-14, 14));
            $awayPrediction = max(0, $actualAway + mt_rand(-14, 14));

            $prediction = Prediction::query()->updateOrCreate([
                'player_id' => $player->id,
                'game_id' => $game->id,
            ], [
                'home_team_prediction' => (string) $homePrediction,
                'away_team_prediction' => (string) $awayPrediction,
            ]);

            if ($prediction->wasRecentlyCreated) {
                $historicalPredictionsCreated++;
            }
        }
    }

    $this->info('Debug prediction load dataset created.');
    $this->line('Group: '.$group->name.' (ULID: '.$group->ulid.')');
    $this->line('Developer user in group: '.$developerUser->email);
    $this->line('Members created in group: '.Member::query()->where('group_id', $group->id)->count());
    $this->line('Players created in group: '.Player::query()->whereIn('member_id', Member::query()->where('group_id', $group->id)->pluck('id'))->count());
    $this->line('Football season: '.$footballSeason->name.' (games: '.$footballGames->count().')');
    $this->line('Basketball season: '.$basketballSeason->name.' (games: '.$basketballGames->count().')');
    $this->line('Historical season: '.$historicalFootballSeason->name.' (games: '.$historicalFootballGames->count().')');
    $this->line('Historical-only players created: '.$historicalPlayers->count());
    $this->line('Occurred games as of today: '.$occurredGames->count());
    $this->line('Predictions generated: '.$createdPredictions);
    $this->line('Historical predictions generated: '.$historicalPredictionsCreated);
    $this->line('Use this user to test as developer: '.$developerUser->email);
})->purpose('Create temporary debug-ready seasons, games, group members, and predictions for leaderboard testing.');
