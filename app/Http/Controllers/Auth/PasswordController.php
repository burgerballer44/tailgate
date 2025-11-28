<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $this->userService->changePassword($request->user(), $validated['password']);

        $this->setFlashAlert('success', 'Password updated successfully!');

        return back();
    }
}
