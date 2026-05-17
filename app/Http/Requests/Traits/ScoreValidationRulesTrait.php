<?php

namespace App\Http\Requests\Traits;

use App\Rules\GameBelongsToFollowedTeam;
use App\Rules\GameTimeNotPassed;
use App\Rules\GameTimeNotPassedForUpdate;
use App\Rules\NoScoreSubmitted;
use Illuminate\Contracts\Validation\ValidationRule;

trait ScoreValidationRulesTrait
{
    /**
     * Get the base validation rules for score fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'home_team_prediction' => ['required', 'integer', 'min:0'],
            'away_team_prediction' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the validation rules for storing a score.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return array_merge($this->baseRules(), [
            'player_id' => ['required', 'exists:players,id'],
            'game_id' => ['required', 'exists:games,id', new GameBelongsToFollowedTeam, new GameTimeNotPassed, new NoScoreSubmitted],
        ]);
    }

    /**
     * Get the validation rules for updating a score.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function updateRules(): array
    {
        return array_merge($this->baseRules(), [
            'score_id' => ['required', new GameTimeNotPassedForUpdate],
        ]);
    }
}
