<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\ApiFormRequest;
use App\Rules\GameMustExistInSeasonGroupFollows;
use App\Rules\GameTimeNotPassed;
use App\Rules\GameTimeNotPassedForUpdate;
use App\Rules\NoScoreSubmitted;
use Illuminate\Validation\Rule;

class UpdateScoreRequest extends ApiFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'score_id' => ['required', new GameTimeNotPassedForUpdate],
            'home_team_prediction' => ['required', 'integer', 'min:0'],
            'away_team_prediction' => ['required', 'integer', 'min:0'],
        ];
    }
}
