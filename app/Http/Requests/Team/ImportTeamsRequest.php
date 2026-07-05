<?php

namespace App\Http\Requests\Team;

use App\DTO\TeamImportData;
use App\Http\Requests\FormRequest;
use App\Services\Contracts\TeamImportManagerInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ImportTeamsRequest extends FormRequest
{
    /**
     * Construct a team import request with dependency injection.
     *
     * @param  TeamImportManagerInterface  $manager  The service that provides available import sources.
     */
    public function __construct(private TeamImportManagerInterface $manager) {}

    /**
     * Authorize administrators to import teams.
     *
     * Authorization is checked at the controller or policy level to ensure only administrators
     * can trigger team imports from external data sources.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the team import request data.
     *
     * Ensures the import source is valid (checked against available sources from TeamImportManager),
     * year is within acceptable range, and optional conference filter is a string.
     *
     * @return array<string, ValidationRule|array|string> The import field validation rules.
     */
    public function rules(): array
    {
        // Validate that the provided source is available from the TeamImportManager.
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
            'conference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Transform validated import data into a DTO for the team import service.
     *
     * Filters out null option values to pass only configured options to the import manager.
     *
     * @return TeamImportData The import configuration transfer object.
     */
    public function toDTO(): TeamImportData
    {
        $validated = $this->validated();

        return new TeamImportData(
            source: $validated['source'],
            options: array_filter([
                'year' => (int) $validated['year'],
                'conference' => $validated['conference'] ?? null,
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
            'Team import failed due to an unexpected error.',
            implode(', ', $validator->errors()->all())
        );

        $exception = new ValidationException($validator);
        $exception->errorBag = $this->errorBag;
        $exception->redirectTo($this->getRedirectUrl());

        throw $exception;
    }
}
