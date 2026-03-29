<?php

namespace App\Http\Controllers\Auth;

use App\DTO\ValidatedUserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use App\Services\Contracts\UserCommandInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private UserCommandInterface $userCommandService
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $this->userCommandService->create(ValidatedUserData::fromArray([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'status' => UserStatus::PENDING,
            'role' => UserRole::REGULAR,
        ]));

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'));
    }
}
