<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handles closed-alpha invite code entry for coming soon bypass access.
 */
final class AlphaAccessController extends Controller
{
    private const BYPASS_SESSION_KEY = 'coming_soon_bypass_granted';

    /**
     * Display the invite code form.
     *
     * @return View Alpha invite code form view.
     */
    public function create(): View
    {
        return view('alpha.index');
    }

    /**
     * Validate the invite code and grant a session-scoped bypass when valid.
     *
     * @param  Request  $request  Current request containing invite code input.
     * @return RedirectResponse Redirect to the home route on success.
     *
     * @throws ValidationException When the invite code is invalid or not configured.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invite_code' => ['required', 'string'],
        ]);

        $configuredCode = (string) config('app.coming_soon_bypass_code', '');

        if ($configuredCode === '' || ! hash_equals($configuredCode, (string) $validated['invite_code'])) {
            throw ValidationException::withMessages([
                'invite_code' => 'The invite code is invalid.',
            ]);
        }

        $request->session()->put(self::BYPASS_SESSION_KEY, true);

        return redirect()->route('home');
    }
}
