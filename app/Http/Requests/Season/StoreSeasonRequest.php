<?php

namespace App\Http\Requests\Season;

use App\Http\Requests\ApiFormRequest;
use App\Models\Common\DateOrString;
use App\Models\SeasonType;
use App\Models\Sport;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreSeasonRequest extends ApiFormRequest
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
            'season_start' => DateOrString::fromString($this->season_start),
            'season_end' => DateOrString::fromString($this->season_end)
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
            'name' => 'required|max:255',
            'sport' => ['required', new Enum(Sport::class)],
            'season_type' => ['required', new Enum(SeasonType::class)],
            'season_start' => ['required', 'string', 'max:255'],
            'season_end' => ['required', 'string', 'max:255'],
        ];
    }
}
