<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedGameData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GameValidationRulesTrait;
use App\Models\Common\DateOrString;
use App\Models\Common\TimeOrString;
use Illuminate\Contracts\Validation\ValidationRule;

class AddGameRequest extends FormRequest
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
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $this->replace([
            'start_date' => DateOrString::fromString($this->start_date),
            'start_time' => TimeOrString::fromString($this->start_time),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->storeRules();
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
