<?php

namespace App\Http\Requests\Season;

use App\Models\SeasonType;
use App\Models\Sport;
use App\DTO\ValidatedSeasonData;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSeasonRequest extends FormRequest
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
            'name' => ['string', 'max:255'],
            'sport' => [new Enum(Sport::class)],
            'season_type' => [new Enum(SeasonType::class)],
            'season_start' => ['date'],
            'season_end' => ['date', 'after:season_start'],
        ];
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
