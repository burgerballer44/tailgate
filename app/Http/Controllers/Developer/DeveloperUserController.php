<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use App\Services\Contracts\UserCommandInterface;
use App\Services\Contracts\UserQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeveloperUserController extends Controller
{
    /**
     * Build the developer user controller with command and query services.
     *
     * @param UserCommandInterface $userCommandService Service responsible for user write operations.
     * @param UserQueryInterface $userQueryService Service responsible for user listing and read projections.
     * @return void Initializes controller dependencies.
     */
    public function __construct(
        private UserCommandInterface $userCommandService,
        private UserQueryInterface $userQueryService
    ) {}

    /**
     * Display a paginated list of users with available role and status filters.
     *
     * @param Request $request Incoming request containing optional filter query values.
     * @return View Renders the developer user index with filtered users and enum-backed filter options.
     */
    public function index(Request $request): View
    {
        return view('developer.users.index', [
            'users' => $this->userQueryService->query($request->all())->paginate(),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
            'roles' => collect(UserRole::cases())->pluck('value'),
        ]);
    }

    /**
     * Show the form for creating a new user.
     *
     * @return View Renders the developer user create form with allowed roles and statuses.
     */
    public function create()
    {
        return view('developer.users.create', [
            'roles' => collect(UserRole::cases())->pluck('value'),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
        ]);
    }

    /**
     * Persist a newly created user from validated payload data.
     *
     * @param StoreUserRequest $request Validated request containing user profile, role, and status values.
     * @return RedirectResponse Redirects to the developer user index after creating the user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userCommandService->create($request->toDTO());

        $this->setFlashAlert('success', 'User created successfully!');

        return redirect()->route('developer.users.index');
    }

    /**
     * Show a single user record in the developer detail view.
     *
     * @param User $user Route-bound user being inspected.
     * @return View Renders the developer user detail page.
     */
    public function show(User $user): View
    {
        return view('developer.users.show', ['user' => $user]);
    }

    /**
     * Show the form for editing an existing user.
     *
     * @param User $user Route-bound user to edit.
     * @return View Renders the developer user edit form with role and status options.
     */
    public function edit(User $user): View
    {
        return view('developer.users.edit', [
            'user' => $user,
            'roles' => collect(UserRole::cases())->pluck('value'),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
        ]);
    }

    /**
     * Update an existing user profile from validated payload data.
     *
     * @param UpdateUserRequest $request Validated request containing updated user attributes.
     * @param User $user Route-bound user that will be updated.
     * @return RedirectResponse Redirects to the developer user index after a successful update.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userCommandService->updateProfile($user, $request->toDTO());

        $this->setFlashAlert('success', 'User updated successfully!');

        return redirect()->route('developer.users.index');
    }

    /**
     * Remove a user from the system.
     *
     * @param User $user Route-bound user to delete.
     * @return RedirectResponse Redirects to the developer user index after deletion.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->userCommandService->delete($user);

        $this->setFlashAlert('success', 'User deleted successfully!');

        return redirect()->route('developer.users.index');
    }
}
