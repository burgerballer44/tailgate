<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupSeasonFollow;
use App\Models\Season;
use App\ScoringPolicies\PredictionDifferenceFromScorePointsPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupSeasonFollow>
 */
class GroupSeasonFollowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'season_id' => Season::factory(),
            'prediction_scoring_policy' => PredictionDifferenceFromScorePointsPolicy::key(),
            'enabled_prediction_policies' => [],
        ];
    }
}
