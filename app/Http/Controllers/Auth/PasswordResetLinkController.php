<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the request form for sending a password reset link.
     *
     * @return View The forgot-password view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the submitted email address.
     *
     * @param  Request  $request  The current request containing the email address to notify.
     * @return RedirectResponse A redirect back to the form with a status message.
     * @throws ValidationException When the email field is invalid or the reset link cannot be sent.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            $this->setFlashAlert('success', __($status));

            return back()->with('status', __($status));
        }

        $this->setFlashAlert('error', __($status));

        return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
