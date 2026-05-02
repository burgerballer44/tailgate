<?php

namespace Database\Factories;

use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization' => fake()->company(),
            'designation' => fake()->name().'designation',
            'mascot' => fake()->optional()->name().'mascot',
            'conference' => fake()->randomElement(['ACC', 'Big Ten', 'SEC', 'Big 12', 'Mountain West', 'Independent']),
            'abbreviation' => fake()->optional()->lexify('???'),
            'color' => fake()->optional()->hexColor(),
            'alternate_color' => fake()->optional()->hexColor(),
            'logos' => fake()->optional()->randomElement([
                ['https://example.test/logo-primary.png'],
                ['https://example.test/logo-primary.png', 'https://example.test/logo-secondary.png'],
            ]),
            'social_media' => fake()->optional()->randomElement([
                [['label' => 'X', 'url' => 'https://x.com/example']],
                [['label' => 'Instagram', 'url' => 'https://instagram.com/example']],
            ]),
            'type' => fake()->randomElement(TeamType::cases()),
        ];
    }

    /**
     * Configure the factory.
     */
    public function configure(): self
    {
        return $this->afterCreating(function (Team $team) {
            // By default, create one random sport for each team
            $team->sports()->create([
                'sport' => fake()->randomElement(Sport::cases())->value,
            ]);
        });
    }

    /**
     * Create a team with specific sports (overrides default behavior).
     */
    public function withSports(array $sports): self
    {
        return $this->afterCreating(function (Team $team) use ($sports) {
            // Remove default sport and add specified ones
            $team->sports()->delete();
            foreach ($sports as $sport) {
                $team->sports()->create([
                    'sport' => $sport instanceof Sport ? $sport->value : $sport,
                ]);
            }
        });
    }

    /**
     * Create a team without any sports.
     */
    public function withoutSports(): self
    {
        return $this->afterCreating(function (Team $team) {
            $team->sports()->delete();
        });
    }
}
