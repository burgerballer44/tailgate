<?php

namespace App\Http\Controllers;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('teams.index');
    }
}
