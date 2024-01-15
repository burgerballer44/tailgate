<?php

use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\PlayerController;
use App\Http\Controllers\API\SeasonController;
use App\Http\Controllers\API\TeamController;
use App\Http\Controllers\API\UserController;
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

Route::resource('users', UserController::class);

Route::resource('teams', TeamController::class);

Route::resource('seasons', SeasonController::class);

Route::prefix('seasons/{season}')->group(function () {
    Route::get('/teams', [SeasonController::class, 'getTeams']);
    Route::resource('games', GameController::class);
});

Route::resource('groups', GroupController::class);

Route::prefix('groups/{group}')->group(function () {

    Route::post('/follow', [GroupController::class, 'followTeam']);
    Route::delete('/follow/{follow}', [GroupController::class, 'removeFollow']);

    Route::resource('members', MemberController::class);

    Route::prefix('members/{member}')->group(function () {
        
        Route::resource('player', PlayerController::class);

        Route::post('/player/{player}/score', [PlayerController::class, 'submitScore']);
        Route::patch('/player/{player}/score/{score}', [PlayerController::class, 'updateScore']);
        Route::delete('/player/{player}/score/{score}', [PlayerController::class, 'destroyScore']);
    });

});