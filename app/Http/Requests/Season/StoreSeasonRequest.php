<?php

namespace App\Http\Requests\Season;

use App\Models\SeasonType;
use App\Models\Sport;
use App\Models\Common\DateOrString;
use App\DTO\ValidatedSeasonData;
use Illuminate\Validation\Rules\Enum;
use App\Http\Requests\FormRequest;

class StoreSeasonRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'sport' => ['required', new Enum(Sport::class)],
            'season_type' => ['required', new Enum(SeasonType::class)],
            'season_start' => ['required', 'date'],
            'season_end' => ['required', 'date', 'after:season_start'],
            'active' => ['required', 'boolean'],
            'active_date' => ['required', 'date'],
            'inactive_date' => ['required', 'date'],
        ];
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
