<?php

namespace App\Http\Controllers;

use App\Models\SeasonType;
use App\Models\Sport;

class SeasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('seasons.index', [
            'seasonTypes' => collect(SeasonType::cases())->pluck('value'),
            'sports' => collect(Sport::cases())->pluck('value'),
        ]);
    }
}
