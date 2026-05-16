<?php

namespace App\Http\Requests;

use App\Traits\FlashAlertTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

abstract class FormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    use FlashAlertTrait;

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
            'Validation failed',
            implode(', ', $validator->errors()->all())
        );

        $exception = new ValidationException($validator);

        $exception->errorBag = $this->errorBag;
        $exception->redirectTo($this->getRedirectUrl());

        throw $exception;
    }
}
