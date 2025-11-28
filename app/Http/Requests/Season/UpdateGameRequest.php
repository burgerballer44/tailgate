<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedGameData;
use App\Models\Common\DateOrString;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $this->replace([
            'start_date' => DateOrString::fromString($this->start_date),
            'start_time' => DateOrString::fromString($this->start_time),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'game_id' => ['required', 'exists:games,id'],
            'season_id' => ['required', 'exists:seasons,id'],
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id', 'different:home_team_id'],
            'home_team_score' => ['required', 'integer', 'min:0'],
            'away_team_score' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get the validated data as a ValidatedGameData object.
     * This method is used to pass validated game data to the service layer.
     *
     * @return ValidatedGameData The validated game data transfer object.
     */
    public function toDTO(): ValidatedGameData
    {
        return ValidatedGameData::fromArray($this->validated());
    }
}
