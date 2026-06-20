<?php

namespace App\Http\Requests\Traits;

use App\Rules\GameBelongsToFollowedTeam;
use App\Rules\NoPredictionSubmitted;

trait PredictionValidationRulesTrait
{
    /**
     * Defines shared validation rules for prediction fields.
     *
    * @return array<string, array|string>
     */
    protected function baseRules(): array
    {
        return [
            'home_team_prediction' => ['required', 'integer', 'min:0'],
            'away_team_prediction' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Defines validation rules used when creating a prediction.
     *
    * @return array<string, array|string>
     */
    protected function storeRules(): array
    {
        return array_merge($this->baseRules(), [
            'player_id' => ['required', 'exists:players,id'],
            'game_id' => ['required', 'exists:games,id', new GameBelongsToFollowedTeam, new NoPredictionSubmitted],
        ]);
    }

    /**
     * Defines validation rules used when updating a prediction.
     *
    * @return array<string, array|string>
     */
    protected function updateRules(): array
    {
        return array_merge($this->baseRules(), [
            'prediction_id' => ['required'],
        ]);
    }
}