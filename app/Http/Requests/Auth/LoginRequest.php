<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Allow any guest to submit the login form.
     *
     * The actual authentication check happens in authenticate(), so authorization only needs to
     * permit the request to reach validation.
     *
     * @return bool True because the login form is available to unauthenticated users.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the credential fields required to attempt authentication.
     *
     * @return array<string, Rule|array|string> The email and password fields accepted by the login form.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the submitted credentials and update the rate limiter.
     *
     * @throws ValidationException When the credentials are invalid or the request is rate limited.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Guard the login flow against excessive failed attempts.
     *
     * @throws ValidationException When the request has already exceeded the allowed attempt count.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Build the per-user throttle key used by the login rate limiter.
     *
     * The email and IP are combined so repeated failures from the same client are grouped while
     * still allowing different accounts to be rate limited independently.
     *
     * @return string The normalized rate-limit key used by RateLimiter.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
