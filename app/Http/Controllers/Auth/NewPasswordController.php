<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Contracts\UserCommandInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Build the password reset controller with user command operations.
     *
     * @param UserCommandInterface $userCommandService Service responsible for persisting password updates.
     */
    public function __construct(
        private UserCommandInterface $userCommandService
    ) {}

    /**
     * Show the password reset form that accepts a token and new credentials.
     *
     * @param  Request  $request  The current request that carries the password reset token.
     * @return View The reset-password view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Reset the user's password, rotate the remember token, and log the event.
     *
     * @param  Request  $request  The current request containing the reset token and credentials.
     * @return RedirectResponse A redirect to the login screen after a successful reset.
     * @throws ValidationException When the reset payload fails validation or the broker rejects it.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user = $this->userCommandService->resetPassword($user, $request->password, Str::random(60));

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            $this->setFlashAlert('success', __($status));

            return redirect()->route('login');
        }

        $this->setFlashAlert('error', __($status));

        return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
