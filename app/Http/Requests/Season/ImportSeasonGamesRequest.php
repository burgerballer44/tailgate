<?php

namespace App\Http\Requests\Season;

use App\DTO\GameImportData;
use App\Http\Requests\FormRequest;
use App\Models\SeasonType;
use App\Services\Contracts\GameImportManagerInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class ImportSeasonGamesRequest extends FormRequest
{
    /**
     * Construct a game import request with dependency injection.
     *
     * @param  GameImportManagerInterface  $manager  The service that provides available import sources.
     */
    public function __construct(private GameImportManagerInterface $manager) {}

    /**
     * Authorize administrators to import games for a season.
     *
     * Authorization is checked at the controller or policy level to ensure only administrators
     * can trigger game imports from external data sources.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the game import request data.
     *
     * Ensures the import source is valid (checked against available sources from GameImportManager),
     * year is within acceptable range, and optional filters are properly formatted.
     *
     * @return array<string, ValidationRule|array|string> The import field validation rules.
     */
    public function rules(): array
    {
        // Validate that the provided source is available from the GameImportManager.
        // This approach ensures validation rules stay synchronized with configured import sources,
        // preventing attempts to import from removed or invalid sources.
        $checkSources = function (string $attribute, mixed $value, $fail) {
            $sources = $this->manager->availableSources();
            $sourceKeys = array_column($sources, 'value');
            if (! in_array($value, $sourceKeys, true)) {
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

    /**
     * Transform validated import data into a DTO for the game import service.
     *
     * Filters out null option values to pass only configured options to the import manager.
     *
     * @return GameImportData The import configuration transfer object.
     */
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

    /**
     * Handle a failed validation attempt with flash messaging.
     *
     * JSON requests receive the standard Laravel validation response. Browser requests receive
     * a flash alert in addition to the validation exception to provide user-facing feedback.
     *
     * @param  Validator  $validator  The validator instance containing failed rules.
     * @return void
     *
     * @throws ValidationException Always thrown with appropriate error bag and redirect location.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        $this->setFlashAlert(
            'error',
            'Game import failed due to an unexpected error.',
            implode(', ', $validator->errors()->all())
        );

        $exception = new ValidationException($validator);
        $exception->errorBag = $this->errorBag;
        $exception->redirectTo($this->getRedirectUrl());

        throw $exception;
    }
}
