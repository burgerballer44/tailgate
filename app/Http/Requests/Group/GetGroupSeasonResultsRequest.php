<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class GetGroupSeasonResultsRequest extends FormRequest
{
    /**
     * Authorize authenticated users.
     *
     * Group membership authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate season-scoped result query input.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $group = $this->route('group');
        $seasonId = (int) $this->input('season_id');

        return [
            'season_id' => [
                'required',
                'integer',
                Rule::exists('group_season_follows', 'season_id')->where(function ($query) use ($group) {
                    $query->where('group_id', $group->id ?? null);
                }),
            ],
            'as_of_game_id' => [
                'nullable',
                'integer',
                Rule::exists('games', 'id')->where(function ($query) use ($seasonId) {
                    $query->where('season_id', $seasonId);
                }),
            ],
        ];
    }
}
