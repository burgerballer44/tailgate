<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Enums\InitialGroupLimitRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'owner_id' => User::factory(),
            'member_limit' => InitialGroupLimitRule::MEMBER_LIMIT->value(),
            'player_limit' => InitialGroupLimitRule::PLAYER_LIMIT->value(),
            'follow_limit' => InitialGroupLimitRule::FOLLOW_LIMIT->value(),
        ];
    }
}
