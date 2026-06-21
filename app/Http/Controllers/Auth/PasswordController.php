<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Contracts\UserCommandInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Build the password controller with the user command service.
     *
     * @param UserCommandInterface $userCommandService Service used to mutate user credentials.
     * @return void Initializes controller dependencies.
     */
    public function __construct(
        private UserCommandInterface $userCommandService
    ) {}

    /**
     * Update the authenticated user's password and keep the flow in the profile area.
     *
     * @param  Request  $request  The current request containing the password change payload.
     * @return RedirectResponse A redirect back to the password form after the update attempt.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $this->userCommandService->changePassword($request->user(), $validated['password']);

        $this->setFlashAlert('success', 'Password updated successfully!');

        return back();
    }
}
