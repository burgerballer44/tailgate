<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedSeasonData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\SeasonValidationRulesTrait;

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
        * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->storeRules();
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
