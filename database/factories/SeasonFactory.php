<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
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
            'active' => fake()->boolean(),
        ];
    }

    /**
     * Indicate that the season is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the season is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
