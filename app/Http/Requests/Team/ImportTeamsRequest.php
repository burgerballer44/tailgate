<?php

namespace App\Http\Requests\Team;

use App\DTO\TeamImportData;
use App\Http\Requests\FormRequest;
use App\Services\Contracts\TeamImportManagerInterface;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ImportTeamsRequest extends FormRequest
{
    public function __construct(private TeamImportManagerInterface $manager) {}    

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // custom validation rule to check if the provided source is valid based on the available sources from the TeamImportManager
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
            'conference' => ['nullable', 'string', 'max:255'],
        ];
    }

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
     * Handle a failed validation attempt.
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