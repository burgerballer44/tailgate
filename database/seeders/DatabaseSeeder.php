<?php

namespace Database\Seeders;

use App\Models\Enums\UserRole;
use App\Models\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
