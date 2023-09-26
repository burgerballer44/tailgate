<?php

use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\PlayerController;
use App\Http\Controllers\API\SeasonController;
use App\Http\Controllers\API\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::resource('teams', TeamController::class);

Route::resource('seasons', SeasonController::class);

Route::prefix('seasons/{season}')->group(function () {
    Route::resource('games', GameController::class);
});

Route::resource('groups', GroupController::class);

Route::prefix('groups/{group}')->group(function () {

    Route::resource('members', MemberController::class);

    Route::prefix('members/{member}')->group(function () {
        Route::post('/player', [PlayerController::class, 'store']);
        Route::patch('/player/{player}', [PlayerController::class, 'update']);
    });
});