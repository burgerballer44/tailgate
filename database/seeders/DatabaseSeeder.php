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
use App\Models\TeamType;
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
        // Create Developer User from environment variables first
        $developerUser = User::factory()->create([
            'name' => env('DEVELOPER_NAME', 'Developer User'),
            'email' => env('DEVELOPER_EMAIL', 'developer@example.com'),
            'email_verified_at' => now(),
            'password' => Hash::make(env('DEVELOPER_PASSWORD', 'password')),
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::DEVELOPER->value,
        ]);
    }
}
