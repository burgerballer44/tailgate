<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialAuthenticationException;
use App\Http\Controllers\Controller;
use App\Services\SocialAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthenticationController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return RedirectResponse Redirect response to the provider authorization endpoint.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google OAuth callback and authenticate the resolved user.
     *
     * @param Request $request Current HTTP request used to regenerate the authenticated session.
     * @param SocialAuthenticationService $socialAuthenticationService Service that maps provider users to local users.
     * @return RedirectResponse Redirects to dashboard on success or back to login with an error message.
     */
    public function callback(Request $request, SocialAuthenticationService $socialAuthenticationService): RedirectResponse
    {
        try {
            $providerUser = Socialite::driver('google')->user();
            $user = $socialAuthenticationService->resolveUserFromProvider(provider: 'google', providerUser: $providerUser);

            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        } catch (SocialAuthenticationException $exception) {
            return redirect()->route('login')->withErrors([
                'email' => 'Unable to sign in with Google account. Please use email/password or try again.',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Google OAuth callback failed.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Unable to sign in right now. Please try again later.',
            ]);
        }
    }
}
