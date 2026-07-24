<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login screen for guest users.
     *
     * @return View The login page view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate the submitted credentials, refresh the session, and record the login time.
     *
     * @param  LoginRequest  $request  The validated login request containing credentials and session state.
     * @return RedirectResponse The intended redirect after a successful login.
     *
     * @throws ValidationException When authentication fails or is rate limited.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->user()?->forceFill([
            'last_login_at' => now(),
        ])->save();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the current user out and invalidate the session.
     *
     * @param  Request  $request  The active request whose session should be cleared.
     * @return RedirectResponse A redirect to the public landing page after logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'impersonator_user_id',
            'impersonator_user_name',
            'impersonated_user_id',
            'impersonated_user_name',
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
