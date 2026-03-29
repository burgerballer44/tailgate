<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\SeasonType;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Some Season Name',
            'sport' => fake()->randomElement(Sport::cases())->value,
            'season_type' => fake()->randomElement(SeasonType::cases())->value,
            'season_start' => '2019-09-01',
            'season_end' => '2099-12-28',
            'active' => fake()->boolean(),
            'active_date' => fake()->date('Y-m-d'),
            'inactive_date' => fake()->date('Y-m-d'),
        ];
    }

    /**
     * Indicate that the season is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
            'active_date' => now()->format('Y-m-d'),
            'inactive_date' => '2099-12-28',
        ]);
    }
}
