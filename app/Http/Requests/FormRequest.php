<?php

namespace App\Http\Requests;

use App\Traits\FlashAlertTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

abstract class FormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    use FlashAlertTrait;

    /**
     * Convert failed validation into a redirecting exception that also flashes a user-facing alert.
     *
     * This method customizes validation error handling for form-based requests. JSON requests
     * receive Laravel's standard JSON validation response. Browser requests receive a flash alert
     * in addition to the standard validation exception, allowing the UI to display both redirect
     * logic and a human-readable error message to the user.
     *
     * @param  Validator  $validator  The validator instance containing all failed validation rules.
     * @return void
     *
     * @throws ValidationException Always thrown with error bag and redirect location set for browser requests.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        $this->setFlashAlert(
            'error',
            'Validation failed',
            implode(', ', $validator->errors()->all())
        );

        $exception = new ValidationException($validator);

        $exception->errorBag = $this->errorBag;
        $exception->redirectTo($this->getRedirectUrl());

        throw $exception;
    }
}
