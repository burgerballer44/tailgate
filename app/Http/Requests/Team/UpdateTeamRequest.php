<?php

namespace App\Http\Requests\Team;

use App\Models\Sport;
use App\DTO\ValidatedTeamData;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
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
            'designation' => ['required', 'string', 'max:255'],
            'mascot' => ['required', 'string', 'max:255'],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => [new Enum(Sport::class)],
        ];
    }

    /**
     * Get the validated data as a ValidatedTeamData object.
     * This method is used to pass validated team data to the service layer.
     *
     * @return ValidatedTeamData The validated team data transfer object.
     */
    public function toDTO(): ValidatedTeamData
    {
        return ValidatedTeamData::fromArray($this->validated());
    }
}
