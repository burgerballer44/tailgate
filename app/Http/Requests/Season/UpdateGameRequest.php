<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedGameData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GameValidationRulesTrait;

class UpdateGameRequest extends FormRequest
{
    use GameValidationRulesTrait;

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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->updateRules();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_time_tbd' => $this->boolean('start_time_tbd'),
        ]);
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
