<?php

namespace Database\Factories;

use App\Models\Common\DateOrString;
use App\Models\SeasonType;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Season>
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
        ];
    }
}
