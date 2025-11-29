<?php

namespace App\Http\Requests;

use App\Traits\FlashAlertTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\RedirectResponse;

abstract class FormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    use FlashAlertTrait;

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return \Illuminate\Http\RedirectResponse|void
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

        return redirect($this->getRedirectUrl())->withInput();
    }
}