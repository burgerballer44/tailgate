<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedSeasonData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\SeasonValidationRulesTrait;
use App\Models\Common\DateOrString;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreSeasonRequest extends FormRequest
{
    use SeasonValidationRulesTrait;

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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->storeRules();
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $this->replace([
            'season_start' => DateOrString::fromString($this->season_start),
            'season_end' => DateOrString::fromString($this->season_end),
            'active_date' => $this->active_date ? DateOrString::fromString($this->active_date) : null,
            'inactive_date' => $this->inactive_date ? DateOrString::fromString($this->inactive_date) : null,
        ]);
    }

    /**
     * Get the validated data as a ValidatedSeasonData object.
     * This method is used to pass validated season data to the service layer.
     *
     * @return ValidatedSeasonData The validated season data transfer object.
     */
    public function toDTO(): ValidatedSeasonData
    {
        return ValidatedSeasonData::fromArray($this->validated());
    }
}
