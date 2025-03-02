<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Foundation\Auth\User;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::paginate(),
            'statuses' => collect(UserStatus::cases())->pluck('value'),
            'roles' => collect(UserRole::cases())->pluck('value')
        ]);
    }

    public function create()
    {
        return view('admin.users.create');
    }
}
