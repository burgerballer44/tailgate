<?php

namespace Database\Factories;

use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamSport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamSport>
 */
class TeamSportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'sport' => fake()->randomElement(Sport::cases())->value,
        ];
    }
}
