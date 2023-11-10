<?php

namespace App\Http\Controllers;

use App\Models\Sport;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('teams.index', [
            'sports' => collect(Sport::cases())->pluck('value')
        ]);
    }
}
