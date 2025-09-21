<?php

namespace App\Http\Requests\Season;

use App\Http\Requests\ApiFormRequest;
use App\Models\Common\DateOrString;

class AddGameRequest extends ApiFormRequest
{
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
            'start_time' => DateOrString::fromString($this->start_time),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id'],
            'start_date' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'string', 'max:255'],
        ];
    }
}
