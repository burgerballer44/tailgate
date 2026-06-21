<?php

namespace App\Http\Requests\Traits;

use App\Rules\GameBelongsToFollowedTeam;
use App\Rules\NoPredictionSubmitted;

trait PredictionValidationRulesTrait
{
    /**
     * Define base validation rules for prediction data.
     *
     * Validates that both team predictions are non-negative integers, typically representing scores.
     *
     * @return array<string, array|string> The base prediction field validation rules.
     */
    protected function baseRules(): array
    {
        return [
            'home_team_prediction' => ['required', 'integer', 'min:0'],
            'away_team_prediction' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Define validation rules for creating a prediction.
     *
     * Ensures the player and game exist, the game belongs to a team the user is following, and
     * no prediction has already been submitted for this game by this player.
     *
     * @return array<string, array|string> The prediction creation validation rules.
     */
    protected function storeRules(): array
    {
        return array_merge($this->baseRules(), [
            'player_id' => ['required', 'exists:players,id'],
            'game_id' => ['required', 'exists:games,id', new GameBelongsToFollowedTeam, new NoPredictionSubmitted],
        ]);
    }

    /**
     * Define validation rules for updating a prediction.
     *
     * Requires identification of which prediction is being updated via prediction_id.
     *
     * @return array<string, array|string> The prediction update validation rules.
     */
    protected function updateRules(): array
    {
        return array_merge($this->baseRules(), [
            'prediction_id' => ['required'],
        ]);
    }
}