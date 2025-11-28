<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\GameMustExistInSeasonGroupFollows;
use App\Rules\GameTimeNotPassed;
use App\Rules\NoScoreSubmitted;

class SubmitScoreRequest extends FormRequest
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
            'player_id' => ['required', 'exists:players,id'],
            'game_id' => ['required', 'exists:games,id', new GameMustExistInSeasonGroupFollows, new GameTimeNotPassed, new NoScoreSubmitted],
            'home_team_prediction' => ['required', 'integer', 'min:0'],
            'away_team_prediction' => ['required', 'integer', 'min:0'],
        ];
    }
}
