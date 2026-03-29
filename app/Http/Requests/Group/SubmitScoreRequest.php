<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedScoreData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\ScoreValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class SubmitScoreRequest extends FormRequest
{
    use ScoreValidationRulesTrait;

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
     * Get the validated data as a ValidatedScoreData object.
     * This method is used to pass validated score data to the service layer.
     *
     * @return ValidatedScoreData The validated score data transfer object.
     */
    public function toDTO(): ValidatedScoreData
    {
        return ValidatedScoreData::fromArray($this->validated());
    }
}
