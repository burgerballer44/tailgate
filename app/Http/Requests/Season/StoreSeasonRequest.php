<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedSeasonData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\SeasonValidationRulesTrait;

class StoreSeasonRequest extends FormRequest
{
    use SeasonValidationRulesTrait;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
        ]);
    }

    /**
     * Authorizes this request in the current application context.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Defines validation rules for this request payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->storeRules();
    }

    /**
     * Maps validated input into a  DTO.
     * This method is used to pass validated season data to the service layer.
     *
     * @return ValidatedSeasonData The validated season data transfer object.
     */
    public function toDTO(): ValidatedSeasonData
    {
        return ValidatedSeasonData::fromArray($this->validated());
    }
}
