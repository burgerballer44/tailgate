<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedGameData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GameValidationRulesTrait;

class AddGameRequest extends FormRequest
{
    use GameValidationRulesTrait;

    /**
     * Authorize administrators to add a game to a season.
     *
     * Authorization is checked at the controller or policy level to ensure only season administrators
     * can add new games to active seasons.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the game creation request data.
     *
     * Ensures the season exists, teams are different, scores are non-negative, and start time
     * is properly formatted.
     *
     * @return array<string, mixed> The game field validation rules.
     */
    public function rules(): array
    {
        return $this->storeRules();
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
