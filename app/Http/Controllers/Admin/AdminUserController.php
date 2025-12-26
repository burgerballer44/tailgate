<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;

class AdminUserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}
    
    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => $this->userService->query($request->all())->paginate(),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
            'roles' => collect(UserRole::cases())->pluck('value'),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => collect(UserRole::cases())->pluck('value'),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->toDTO());

        $this->setFlashAlert('success', 'User created successfully!');

        return redirect()->route('admin.users.index');
    }

    public function show(User $user): View
    {
        return view('admin.users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => collect(UserRole::cases())->pluck('value'),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->updateProfile($user, $request->toDTO());

        $this->setFlashAlert('success', 'User updated successfully!');

        return redirect()->route('admin.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->delete($user);

        $this->setFlashAlert('success', 'User deleted successfully!');

        return redirect()->route('admin.users.index');
    }
}
