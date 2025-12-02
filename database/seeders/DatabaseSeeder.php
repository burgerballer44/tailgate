<?php

namespace Database\Seeders;

use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\Player;
use App\Models\Score;
use App\Models\Season;
use App\Models\SeasonType;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User from environment variables first
        $adminUser = User::factory()->create([
            'name' => env('ADMIN_NAME', 'Admin User'),
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'email_verified_at' => now(),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::ADMIN->value,
        ]);

        // Create Users
        $userVerifiedEmail = User::factory()->create([
            'name' => 'UserVerifiedEmail',
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ]);

        $userNeedsEmailVerification = User::factory()->create([
            'name' => 'UserNeedsEmailVerification',
            'email' => 'pending@example.com',
            'email_verified_at' => null,
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::REGULAR->value,
        ]);


        $userDeleted = User::factory()->create([
            'name' => 'UserDeleted',
            'email' => 'deleted@example.com',
            'email_verified_at' => now(),
            'status' => UserStatus::DELETED->value,
            'role' => UserRole::REGULAR->value,
        ]);

        $userRegular2 = User::factory()->create([
            'name' => 'UserRegular2',
            'email' => 'regular2@example.com',
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ]);

        // Additional users for the big group
        $users = [];
        for ($i = 3; $i <= 12; $i++) {
            $users[] = User::factory()->create([
                'name' => 'UserRegular'.$i,
                'email' => 'regular'.$i.'@example.com',
                'email_verified_at' => now(),
                'status' => UserStatus::ACTIVE->value,
                'role' => UserRole::REGULAR->value,
            ]);
        }

        // Standalone users not associated with anything
        User::factory()->create([
            'name' => 'StandaloneUser1',
            'email' => 'standalone1@example.com',
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ]);

        User::factory()->create([
            'name' => 'StandaloneUser2',
            'email' => 'standalone2@example.com',
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ]);

        User::factory()->create([
            'name' => 'StandaloneUser3',
            'email' => 'standalone3@example.com',
            'email_verified_at' => null,
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::REGULAR->value,
        ]);

        // Create Teams
        $teamWithGames1 = Team::factory()->create([
            'designation' => 'Eagles',
            'mascot' => 'Bird',
        ]);

        $teamWithGames2 = Team::factory()->create([
            'designation' => 'Falcons',
            'mascot' => 'Bird of Prey',
        ]);

        $teamNoGames1 = Team::factory()->create([
            'designation' => 'Ravens',
            'mascot' => 'Raven',
        ]);

        $teamNoGames2 = Team::factory()->create([
            'designation' => 'Cardinals',
            'mascot' => 'Cardinal',
        ]);

        // Standalone teams not associated with anything
        Team::factory()->create([
            'designation' => 'Lions',
            'mascot' => 'Lion',
        ]);

        Team::factory()->create([
            'designation' => 'Tigers',
            'mascot' => 'Tiger',
        ]);

        Team::factory()->create([
            'designation' => 'Bears',
            'mascot' => 'Bear',
        ]);

        // Create Seasons
        $seasonFewGames = Season::factory()->create([
            'name' => 'SeasonFewGames',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2024-09-01',
            'season_end' => '2024-12-31',
            'active' => true,
            'active_date' => '2024-09-01',
            'inactive_date' => '2024-12-31',
        ]);

        $seasonManyGames = Season::factory()->create([
            'name' => 'SeasonManyGames',
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2024-10-01',
            'season_end' => '2025-04-30',
            'active' => true,
            'active_date' => '2024-10-01',
            'inactive_date' => '2025-04-30',
        ]);

        $seasonPreseason = Season::factory()->create([
            'name' => 'SeasonPreseason',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::PRE->value,
            'season_start' => '2024-08-01',
            'season_end' => '2024-08-31',
            'active' => false,
            'active_date' => null,
            'inactive_date' => '2024-08-31',
        ]);

        $seasonPostseason = Season::factory()->create([
            'name' => 'SeasonPostseason',
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::POST->value,
            'season_start' => '2025-05-01',
            'season_end' => '2025-06-30',
            'active' => false,
            'active_date' => null,
            'inactive_date' => null,
        ]);

        // Older inactive seasons not in use
        Season::factory()->create([
            'name' => 'Season2022Football',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2022-09-01',
            'season_end' => '2022-12-31',
            'active' => false,
            'active_date' => '2022-09-01',
            'inactive_date' => '2022-12-31',
        ]);

        Season::factory()->create([
            'name' => 'Season2023Basketball',
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2023-10-01',
            'season_end' => '2024-04-30',
            'active' => false,
            'active_date' => '2023-10-01',
            'inactive_date' => '2024-04-30',
        ]);

        Season::factory()->create([
            'name' => 'Season2021Football',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2021-09-01',
            'season_end' => '2021-12-31',
            'active' => false,
            'active_date' => '2021-09-01',
            'inactive_date' => '2021-12-31',
        ]);

        // Create Games for seasons
        // SeasonFewGames: 4 games
        for ($i = 1; $i <= 4; $i++) {
            Game::factory()->create([
                'season_id' => $seasonFewGames->id,
                'home_team_id' => $i % 2 == 0 ? $teamWithGames1->id : $teamWithGames2->id,
                'away_team_id' => $i % 2 == 0 ? $teamWithGames2->id : $teamWithGames1->id,
                'home_team_score' => rand(20, 35),
                'away_team_score' => rand(20, 35),
                'start_date' => '2024-09-'.str_pad($i*7, 2, '0', STR_PAD_LEFT),
                'start_time' => '19:00',
            ]);
        }

        // SeasonManyGames: 45 games (to keep under 50 total)
        for ($i = 1; $i <= 45; $i++) {
            Game::factory()->create([
                'season_id' => $seasonManyGames->id,
                'home_team_id' => $i % 2 == 0 ? $teamWithGames1->id : $teamWithGames2->id,
                'away_team_id' => $i % 2 == 0 ? $teamWithGames2->id : $teamWithGames1->id,
                'home_team_score' => rand(90, 120),
                'away_team_score' => rand(90, 120),
                'start_date' => '2024-10-'.str_pad($i % 30 + 1, 2, '0', STR_PAD_LEFT),
                'start_time' => '20:00',
            ]);
        }

        // SeasonPreseason: 3 games
        for ($i = 1; $i <= 3; $i++) {
            Game::factory()->create([
                'season_id' => $seasonPreseason->id,
                'home_team_id' => $i % 2 == 0 ? $teamWithGames1->id : $teamWithGames2->id,
                'away_team_id' => $i % 2 == 0 ? $teamWithGames2->id : $teamWithGames1->id,
                'home_team_score' => rand(15, 30),
                'away_team_score' => rand(15, 30),
                'start_date' => '2024-08-'.str_pad($i*5, 2, '0', STR_PAD_LEFT),
                'start_time' => '19:00',
            ]);
        }

        // SeasonPostseason: 6 games
        for ($i = 1; $i <= 6; $i++) {
            Game::factory()->create([
                'season_id' => $seasonPostseason->id,
                'home_team_id' => $i % 2 == 0 ? $teamWithGames1->id : $teamWithGames2->id,
                'away_team_id' => $i % 2 == 0 ? $teamWithGames2->id : $teamWithGames1->id,
                'home_team_score' => rand(95, 125),
                'away_team_score' => rand(95, 125),
                'start_date' => '2025-05-'.str_pad($i*3, 2, '0', STR_PAD_LEFT),
                'start_time' => '20:00',
            ]);
        }

        // Create Groups
        $groupNoMembers = Group::factory()->create([
            'name' => 'GroupNoMembers',
            'owner_id' => $userVerifiedEmail->id,
            'invite_code' => Str::random(10),
            'member_limit' => 10,
            'player_limit' => 20,
        ]);

        $groupSingleAdmin = Group::factory()->create([
            'name' => 'GroupSingleAdmin',
            'owner_id' => $userVerifiedEmail->id,
            'invite_code' => Str::random(10),
            'member_limit' => 10,
            'player_limit' => 20,
        ]);

        $groupMultipleAdmins = Group::factory()->create([
            'name' => 'GroupMultipleAdmins',
            'owner_id' => $userVerifiedEmail->id,
            'invite_code' => Str::random(10),
            'member_limit' => 10,
            'player_limit' => 20,
        ]);

        $groupWithMembers = Group::factory()->create([
            'name' => 'GroupWithMembers',
            'owner_id' => $userVerifiedEmail->id,
            'invite_code' => Str::random(10),
            'member_limit' => 10,
            'player_limit' => 20,
        ]);

        // Big group that follows a team with 10 members and multiple players
        $groupBigFamily = Group::factory()->create([
            'name' => 'GroupBigFamily',
            'owner_id' => $userVerifiedEmail->id,
            'invite_code' => Str::random(10),
            'member_limit' => 50,
            'player_limit' => 100,
        ]);

        $groupNoFollow = Group::factory()->create([
            'name' => 'GroupNoFollow',
            'owner_id' => $userRegular2->id,
            'invite_code' => Str::random(10),
            'member_limit' => 10,
            'player_limit' => 20,
        ]);

        // Create Members
        // GroupSingleAdmin: only owner
        Member::factory()->create([
            'group_id' => $groupSingleAdmin->id,
            'user_id' => $userVerifiedEmail->id,
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);

        // GroupMultipleAdmins: owner + admin user
        Member::factory()->create([
            'group_id' => $groupMultipleAdmins->id,
            'user_id' => $userVerifiedEmail->id,
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);
        Member::factory()->create([
            'group_id' => $groupMultipleAdmins->id,
            'user_id' => $userRegular2->id,
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);

        // GroupWithMembers: owner + regular users
        Member::factory()->create([
            'group_id' => $groupWithMembers->id,
            'user_id' => $userVerifiedEmail->id,
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);
        Member::factory()->create([
            'group_id' => $groupWithMembers->id,
            'user_id' => $userRegular2->id,
            'role' => GroupRole::GROUP_MEMBER->value,
        ]);

        // GroupBigFamily: 10 members with multiple players each
        $bigGroupMembers = [];
        for ($i = 0; $i < 10; $i++) {
            $member = Member::factory()->create([
                'group_id' => $groupBigFamily->id,
                'user_id' => $users[$i]->id,
                'role' => $i < 2 ? GroupRole::GROUP_ADMIN->value : GroupRole::GROUP_MEMBER->value, // First 2 are admins
            ]);
            $bigGroupMembers[] = $member;
        }

        // GroupNoFollow: just owner
        Member::factory()->create([
            'group_id' => $groupNoFollow->id,
            'user_id' => $userRegular2->id,
            'role' => GroupRole::GROUP_ADMIN->value,
        ]);

        // Create Players for members in GroupWithMembers
        $member1 = Member::where('group_id', $groupWithMembers->id)->where('user_id', $userVerifiedEmail->id)->first();
        $member2 = Member::where('group_id', $groupWithMembers->id)->where('user_id', $userRegular2->id)->first();

        Player::factory()->create([
            'member_id' => $member1->id,
            'player_name' => 'Player1',
        ]);
        Player::factory()->create([
            'member_id' => $member1->id,
            'player_name' => 'Player2',
        ]);
        Player::factory()->create([
            'member_id' => $member2->id,
            'player_name' => 'Player3',
        ]);

        // Create Players for Big Family Group - multiple players per member with parent-child naming
        $playerNames = [
            ['Dad', 'Mom', 'Son'],
            ['Parent1', 'Parent2', 'Child1', 'Child2'],
            ['Father', 'Mother', 'Kid'],
            ['Papa', 'Mama', 'Baby'],
            ['ParentA', 'ParentB', 'Offspring'],
            ['Guardian1', 'Guardian2', 'Dependent1', 'Dependent2'],
            ['Adult1', 'Adult2', 'Youth'],
            ['Senior', 'Junior1', 'Junior2'],
            ['Head', 'Deputy', 'Apprentice'],
            ['Leader', 'Follower1', 'Follower2', 'Follower3'],
        ];

        $bigGroupPlayers = [];
        foreach ($bigGroupMembers as $index => $member) {
            $names = $playerNames[$index];
            foreach ($names as $name) {
                $player = Player::factory()->create([
                    'member_id' => $member->id,
                    'player_name' => 'UserRegular'.($index+3).'-'.$name,
                ]);
                $bigGroupPlayers[] = $player;
            }
        }

        // Create Follows - some groups follow teams, some don't
        Follow::factory()->create([
            'group_id' => $groupWithMembers->id,
            'team_id' => $teamWithGames1->id,
            'season_id' => $seasonFewGames->id,
        ]);

        Follow::factory()->create([
            'group_id' => $groupWithMembers->id,
            'team_id' => $teamWithGames2->id,
            'season_id' => $seasonManyGames->id,
        ]);

        // Big Family Group follows Eagles team and Few Games season
        Follow::factory()->create([
            'group_id' => $groupBigFamily->id,
            'team_id' => $teamWithGames1->id,
            'season_id' => $seasonFewGames->id,
        ]);

        // GroupMultipleAdmins follows Falcons team and Many Games season
        Follow::factory()->create([
            'group_id' => $groupMultipleAdmins->id,
            'team_id' => $teamWithGames2->id,
            'season_id' => $seasonManyGames->id,
        ]);

        // GroupNoFollow and GroupNoMembers don't follow any teams
        // GroupSingleAdmin doesn't follow any teams

        // Create Scores for players
        $player1 = Player::where('player_name', 'Player1')->first();
        $player2 = Player::where('player_name', 'Player2')->first();
        $player3 = Player::where('player_name', 'Player3')->first();

        $games = Game::where('season_id', $seasonFewGames->id)->take(2)->get();
        foreach ($games as $game) {
            Score::factory()->create([
                'player_id' => $player1->id,
                'game_id' => $game->id,
                'home_team_prediction' => rand(20, 35),
                'away_team_prediction' => rand(20, 35),
            ]);
            Score::factory()->create([
                'player_id' => $player2->id,
                'game_id' => $game->id,
                'home_team_prediction' => rand(20, 35),
                'away_team_prediction' => rand(20, 35),
            ]);
        }

        // Create scores for all players in Big Family Group for all games in followed season
        $fewGames = Game::where('season_id', $seasonFewGames->id)->get();
        foreach ($bigGroupPlayers as $player) {
            foreach ($fewGames as $game) {
                Score::factory()->create([
                    'player_id' => $player->id,
                    'game_id' => $game->id,
                    'home_team_prediction' => rand(20, 35),
                    'away_team_prediction' => rand(20, 35),
                ]);
            }
        }

        // Create some scores for GroupMultipleAdmins players
        $adminMember1 = Member::where('group_id', $groupMultipleAdmins->id)->where('user_id', $userVerifiedEmail->id)->first();
        $adminMember2 = Member::where('group_id', $groupMultipleAdmins->id)->where('user_id', $userRegular2->id)->first();

        if ($adminMember1) {
            $adminPlayer1 = Player::factory()->create([
                'member_id' => $adminMember1->id,
                'player_name' => 'AdminPlayer1',
            ]);

            $manyGames = Game::where('season_id', $seasonManyGames->id)->take(5)->get();
            foreach ($manyGames as $game) {
                Score::factory()->create([
                    'player_id' => $adminPlayer1->id,
                    'game_id' => $game->id,
                    'home_team_prediction' => rand(90, 120),
                    'away_team_prediction' => rand(90, 120),
                ]);
            }
        }

        if ($adminMember2) {
            $adminPlayer2 = Player::factory()->create([
                'member_id' => $adminMember2->id,
                'player_name' => 'RegularPlayer2',
            ]);

            $manyGames = Game::where('season_id', $seasonManyGames->id)->take(5)->get();
            foreach ($manyGames as $game) {
                Score::factory()->create([
                    'player_id' => $adminPlayer2->id,
                    'game_id' => $game->id,
                    'home_team_prediction' => rand(90, 120),
                    'away_team_prediction' => rand(90, 120),
                ]);
            }
        }
    }
}
