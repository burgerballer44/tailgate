<?php

namespace Database\Factories;

use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
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
            'designation' => fake()->name().'designation',
            'mascot' => fake()->name().'mascot',
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
                    'sport' => $sport instanceof Sport ? $sport->value : $sport
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
