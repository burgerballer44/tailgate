<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index()
    {
        return view('admin.users.index', [
            'users' => User::paginate(),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
            'roles' => collect(UserRole::cases())->pluck('value'),
        ]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:'.implode(',', array_column(UserStatus::cases(), 'value')),
            'role' => 'required|in:'.implode(',', array_column(UserRole::cases(), 'value')),
        ]);

        $this->userService->create($validated);

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }
}
