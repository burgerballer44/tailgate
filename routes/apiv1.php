<?php

use App\Http\Controllers\API\GroupController;
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
    Route::prefix('/game')->group(function () {
        Route::post('/', [SeasonController::class, 'addGame']);
        Route::patch('/{game}', [SeasonController::class, 'updateGame']);
        Route::delete('/{game}', [SeasonController::class, 'destroyGame']);
    });
});

Route::resource('groups', GroupController::class);