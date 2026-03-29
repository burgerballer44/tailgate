<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use App\Services\Contracts\UserCommandInterface;
use App\Services\Contracts\UserQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;

class DeveloperUserController extends Controller
{
    public function __construct(
        private UserCommandInterface $userCommandService,
        private UserQueryInterface $userQueryService
    ) {}
    
    public function index(Request $request): View
    {
        return view('developer.users.index', [
            'users' => $this->userQueryService->query($request->all())->paginate(),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
            'roles' => collect(UserRole::cases())->pluck('value'),
        ]);
    }

    public function create()
    {
        return view('developer.users.create', [
            'roles' => collect(UserRole::cases())->pluck('value'),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userCommandService->create($request->toDTO());

        $this->setFlashAlert('success', 'User created successfully!');

        return redirect()->route('developer.users.index');
    }

    public function show(User $user): View
    {
        return view('developer.users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        return view('developer.users.edit', [
            'user' => $user,
            'roles' => collect(UserRole::cases())->pluck('value'),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userCommandService->updateProfile($user, $request->toDTO());

        $this->setFlashAlert('success', 'User updated successfully!');

        return redirect()->route('developer.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userCommandService->delete($user);

        $this->setFlashAlert('success', 'User deleted successfully!');

        return redirect()->route('developer.users.index');
    }
}
