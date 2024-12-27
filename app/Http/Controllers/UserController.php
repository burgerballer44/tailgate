<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use App\Models\UserStatus;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.users.index', [
            'statuses' => collect(UserStatus::cases())->pluck('value'),
            'roles' => collect(UserRole::cases())->pluck('value')
        ]);
    }
}
