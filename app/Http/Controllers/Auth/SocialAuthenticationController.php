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
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google OAuth callback and authenticate the resolved user.
     */
    public function callback(Request $request, SocialAuthenticationService $socialAuthenticationService): RedirectResponse
    {
        try {
            $providerUser = Socialite::driver('google')->user();
            $user = $socialAuthenticationService->resolveUserFromProvider(provider: 'google', providerUser: $providerUser);

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
