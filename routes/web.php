<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Admin\AdminGameController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminMemberController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminSeasonController;
use App\Http\Controllers\Admin\AdminTeamController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// cannot be not signed in
Route::middleware('guest')->group(function () {
    // home page
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// must be signed in
Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // must be verified
    Route::middleware('verified')->group(function () {
        // dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
        });

        // groups
        Route::prefix('groups')->name('groups.')->group(function () {
            Route::get('create', [GroupController::class, 'create'])->name('create');
            Route::post('/', [GroupController::class, 'store'])->name('store');
            Route::get('join', [GroupController::class, 'join'])->name('join');
            Route::post('join', [GroupController::class, 'requestJoin'])->name('request-join');
        });

        // This is the admin area.
        // Only users with admin privileges can access these routes.
        // These routes are all inntended for managing the application data outside of normal user interactions.
        Route::prefix('admin')->name('admin.')->middleware('role:Admin')->group(function () {
            Route::resource('users', AdminUserController::class);

            Route::resource('teams', AdminTeamController::class);

            Route::resource('seasons', AdminSeasonController::class);

            Route::resource('seasons.games', AdminGameController::class);

            Route::resource('groups', AdminGroupController::class);

            Route::get('groups/{group}/follow-team', [AdminGroupController::class, 'createFollowTeam'])->name('groups.follow-team.create');
            Route::post('groups/{group}/follow-team', [AdminGroupController::class, 'followTeam'])->name('groups.follow-team');
            Route::delete('groups/{group}/follow/{follow}', [AdminGroupController::class, 'removeFollow'])->name('groups.follow.destroy');

            Route::resource('groups.members', AdminMemberController::class);

            Route::resource('groups.members.players', AdminPlayerController::class);

            Route::get('groups/{group}/members/{member}/players/{player}/submit-score', [AdminPlayerController::class, 'createScore'])->name('groups.members.players.submit-score.create');
            Route::post('groups/{group}/members/{member}/players/{player}/submit-score', [AdminPlayerController::class, 'submitScore'])->name('groups.members.players.submit-score');
            Route::get('groups/{group}/members/{member}/players/{player}/scores/{score}/edit', [AdminPlayerController::class, 'editScore'])->name('groups.members.players.scores.edit');
            Route::patch('groups/{group}/members/{member}/players/{player}/scores/{score}', [AdminPlayerController::class, 'updateScore'])->name('groups.members.players.scores.update');
            Route::delete('groups/{group}/members/{member}/players/{player}/scores/{score}', [AdminPlayerController::class, 'destroyScore'])->name('groups.members.players.scores.destroy');
        });
    });
});
