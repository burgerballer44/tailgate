<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedFollowData;
use App\Http\Requests\FormRequest;
use App\Rules\SeasonIsActive;

class FollowTeamRequest extends FormRequest
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
            'team_id' => ['required', 'exists:teams,id'],
            'season_id' => ['required', 'exists:seasons,id', new SeasonIsActive],
        ];
    }

    /**
     * Get the validated data as a ValidatedFollowData object.
     * This method is used to pass validated follow data to the service layer.
     *
     * @return ValidatedFollowData The validated follow data transfer object.
     */
    public function toDTO(): ValidatedFollowData
    {
        return ValidatedFollowData::fromArray($this->validated());
    }
}
