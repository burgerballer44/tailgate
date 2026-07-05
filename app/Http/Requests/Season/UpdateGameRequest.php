<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedGameData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GameValidationRulesTrait;

class UpdateGameRequest extends FormRequest
{
    use GameValidationRulesTrait;

    /**
     * Authorize administrators to update a game.
     *
     * Authorization is checked at the controller or policy level to ensure only season administrators
     * can modify game details.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the game update request data.
     *
     * Ensures the updated season, teams, scores, and start time conform to game rules.
     *
     * @return array<string, mixed> The game field validation rules.
     */
    public function rules(): array
    {
        return $this->updateRules();
    }

    /**
     * Prepare the data for validation by converting the start_time_tbd field to a boolean.
     *
     * This ensures that the checkbox field is properly converted to a boolean value before
     * validation runs, allowing the validation rules to expect a boolean type.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_time_tbd' => $this->boolean('start_time_tbd'),
        ]);
    }

    /**
     * Transform validated game data into a data transfer object for the service layer.
     *
     * @return ValidatedGameData The validated game data transfer object.
     */
    public function toDTO(): ValidatedGameData
    {
        return ValidatedGameData::fromArray($this->validated());
    }
}
