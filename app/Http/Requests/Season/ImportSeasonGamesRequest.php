<?php

namespace App\Http\Requests\Season;

use App\DTO\GameImportData;
use App\Http\Requests\FormRequest;
use App\Models\SeasonType;
use App\Services\Contracts\GameImportManagerInterface;
use Illuminate\Validation\Rules\Enum;

class ImportSeasonGamesRequest extends FormRequest
{
    public function __construct(private GameImportManagerInterface $manager) {}  

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // custom validation rule to check if the provided source is valid based on the available sources from the GameImportManager
        $checkSources = function(string $attribute, mixed $value, $fail) {
            $sources = $this->manager->availableSources();
            $sourceKeys = array_column($sources, 'value');
            if (!in_array($value, $sourceKeys, true)) {
                $fail("The selected {$attribute} is not a valid import source.");
            }
        };
        
        return [
            'source' => ['required', 'string', $checkSources],
            'year' => ['required', 'integer', 'between:1900,2100'],
            'season_type' => ['nullable', 'string', new Enum(SeasonType::class)],
            'week' => ['nullable', 'integer', 'between:1,30'],
        ];
    }

    public function toDTO(): GameImportData
    {
        $validated = $this->validated();

        return new GameImportData(
            source: $validated['source'],
            options: array_filter([
                'year' => (int) $validated['year'],
                'season_type' => $validated['season_type'],
                'week' => $validated['week'] ?? null,
            ], static fn (mixed $value): bool => $value !== null)
        );
    }
}