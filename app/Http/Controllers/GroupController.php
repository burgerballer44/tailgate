<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('groups.index', [
            // show ids in this case since we need them for group owner
            'users' => UserResource::collection(User::get()->makeVisible(['id'])),
        ]);
    }
}
