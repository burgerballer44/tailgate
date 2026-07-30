<?php

namespace App\Http\Requests\Traits;

use App\Services\Contracts\SeasonQueryInterface;
use Illuminate\Validation\Rule;

trait FollowValidationRulesTrait
{
    /**
     * Define base validation rules for team follow data.
     *
     * Ensures the team exists and the specified sport is valid for that team if provided.
     *
     * @return array<string, mixed> The team and season validation rules.
     */
    protected function baseRules(): array
    {
        return [
            'team_id' => ['required', 'exists:teams,id'],
            'season_ids' => ['required', 'array', 'min:1'],
            'season_ids.*' => ['integer', Rule::in($this->activeSeasonIdsForFollow())],
        ];
    }

    /**
     * Returns active season IDs valid for follow selection.
     *
     * @return array<int, int>
     */
    protected function activeSeasonIdsForFollow(): array
    {
        return app(SeasonQueryInterface::class)
            ->getAvailableSeasonsForFollow()
            ->pluck('id')
            ->map(fn ($seasonId): int => (int) $seasonId)
            ->all();
    }

    /**
     * Define validation rules for creating a follow relationship.
     *
     * @return array<string, mixed> The team follow validation rules.
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Define validation rules for updating a follow relationship.
     *
     * @return array<string, mixed> The team follow validation rules.
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}
