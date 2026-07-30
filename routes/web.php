<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthenticationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Developer\DeveloperGameController;
use App\Http\Controllers\Developer\DeveloperGroupController;
use App\Http\Controllers\Developer\DeveloperImpersonationController;
use App\Http\Controllers\Developer\DeveloperMemberController;
use App\Http\Controllers\Developer\DeveloperPlayerController;
use App\Http\Controllers\Developer\DeveloperRuleController;
use App\Http\Controllers\Developer\DeveloperSeasonController;
use App\Http\Controllers\Developer\DeveloperTeamController;
use App\Http\Controllers\Developer\DeveloperUserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PlayerController;
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

    // social authentication routes
    Route::get('/auth/redirect', [SocialAuthenticationController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/callback', [SocialAuthenticationController::class, 'callback'])->name('auth.google.callback');
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
    Route::post('developer/impersonation/stop', [DeveloperImpersonationController::class, 'stop'])->name('developer.impersonation.stop');

    // must be verified
    Route::middleware('verified')->group(function () {
        // dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/quick-predictions', [DashboardController::class, 'quickPredictions'])->name('dashboard.quick-predictions');

        // profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
        });

        // groups
        Route::prefix('groups')->name('groups.')->group(function () {
            // these routes are for creating and joining groups, so they don't require any group membership
            Route::get('create', [GroupController::class, 'create'])->name('create');
            Route::post('/', [GroupController::class, 'store'])->name('store');
            Route::get('join', [GroupController::class, 'join'])->name('join');
            Route::post('join', [GroupController::class, 'requestJoin'])->name('request-join');

            // these routes require the user to be an approved member of the group
            Route::middleware('user.group.member')->group(function () {
                Route::get('{group}', [GroupController::class, 'show'])->name('show');
                Route::get('{group}/season-results', [GroupController::class, 'seasonResults'])->name('season-results');
                Route::delete('{group}/leave', [GroupController::class, 'leaveGroup'])->name('leave');
                Route::post('{group}/players/{player}/predictions', [GroupController::class, 'storePrediction'])->name('predictions.store');
                Route::patch('{group}/players/{player}/predictions/{prediction}', [GroupController::class, 'updatePrediction'])->name('predictions.update');
            });

            // player management routes - nested resource for managing players within groups
            Route::middleware(['group.member.belongs', 'group.member.approved'])
                ->resource('{group}/members.players', PlayerController::class)
                ->except(['index', 'show']);

            Route::middleware(['user.group.admin', 'group.member.belongs', 'group.member.approved'])->prefix('{group}/manage')->name('manage.')->group(function () {
                Route::get('members/{member}/players', [PlayerController::class, 'index'])->name('members.players.index');
                Route::get('members/{member}/players/create', [PlayerController::class, 'create'])->name('members.players.create');
                Route::post('members/{member}/players', [PlayerController::class, 'store'])->name('members.players.store');
                Route::get('members/{member}/players/{player}/edit', [PlayerController::class, 'edit'])->name('members.players.edit');
                Route::put('members/{member}/players/{player}', [PlayerController::class, 'update'])->name('members.players.update');
                Route::delete('members/{member}/players/{player}', [PlayerController::class, 'destroy'])->name('members.players.destroy');
            });

            // these routes require the user to be an approved admin of the group
            Route::middleware('user.group.admin')->group(function () {
                Route::get('{group}/edit', [GroupController::class, 'edit'])->name('edit');
                Route::patch('{group}', [GroupController::class, 'update'])->name('update');
                Route::patch('{group}/season-follows', [GroupController::class, 'updateSeasonFollows'])->name('update-season-follows');
                Route::patch('{group}/prediction-scoring-policy', [GroupController::class, 'updatePredictionScoringPolicy'])->name('update-prediction-scoring-policy');
                Route::patch('{group}/prediction-policies', [GroupController::class, 'updatePolicies'])->name('update-policies');
                Route::post('{group}/approve/{member}', [GroupController::class, 'approveMember'])
                    ->middleware('group.member.belongs')
                    ->name('approve-member');
                Route::post('{group}/reject/{member}', [GroupController::class, 'rejectMember'])
                    ->middleware('group.member.belongs')
                    ->name('reject-member');
                Route::post('{group}/promote/{member}', [GroupController::class, 'promoteMember'])
                    ->middleware(['group.member.belongs', 'group.member.approved'])
                    ->name('promote-member');
                Route::post('{group}/demote/{member}', [GroupController::class, 'demoteMember'])
                    ->middleware(['group.member.belongs', 'group.member.approved'])
                    ->name('demote-member');
                Route::delete('{group}/remove/{member}', [GroupController::class, 'removeMember'])
                    ->middleware(['group.member.belongs', 'group.member.approved'])
                    ->name('remove-member');
                Route::get('{group}/follow-team', [GroupController::class, 'createFollowTeam'])->name('follow-team.create');
                Route::post('{group}/follow-team', [GroupController::class, 'followTeam'])->name('follow-team');
                Route::delete('{group}/follow/{follow}', [GroupController::class, 'removeFollow'])
                    ->middleware('group.follow.belongs')
                    ->name('follow.destroy');
            });
        });

        // This is the developer area.
        // Only users with developer privileges can access these routes.
        // These routes are all intended for managing the application data outside of normal user interactions.
        Route::prefix('developer')->name('developer.')->middleware('role:Developer')->group(function () {
            Route::get('rules', [DeveloperRuleController::class, 'index'])->name('rules.index');
            Route::post('impersonation/{user}', [DeveloperImpersonationController::class, 'start'])->name('impersonation.start');

            Route::resource('users', DeveloperUserController::class);

            Route::get('teams/import-teams', [DeveloperTeamController::class, 'importTeams'])->name('teams.import-teams');
            Route::post('teams/import-teams', [DeveloperTeamController::class, 'storeImportedTeams'])->name('teams.import-teams.store');

            Route::resource('teams', DeveloperTeamController::class);

            Route::get('seasons/{season}/import-games', [DeveloperSeasonController::class, 'importGames'])->name('seasons.import-games');
            Route::post('seasons/{season}/import-games', [DeveloperSeasonController::class, 'storeImportedGames'])->name('seasons.import-games.store');

            Route::resource('seasons', DeveloperSeasonController::class);

            Route::resource('seasons.games', DeveloperGameController::class);

            Route::resource('groups', DeveloperGroupController::class);
            Route::get('groups/{group}/season-results', [DeveloperGroupController::class, 'seasonResults'])->name('groups.season-results');

            Route::get('groups/{group}/follow-team', [DeveloperGroupController::class, 'createFollowTeam'])->name('groups.follow-team.create');
            Route::post('groups/{group}/follow-team', [DeveloperGroupController::class, 'followTeam'])->name('groups.follow-team');
            Route::delete('groups/{group}/follow/{follow}', [DeveloperGroupController::class, 'removeFollow'])->name('groups.follow.destroy');

            Route::resource('groups.members', DeveloperMemberController::class);

            Route::resource('groups.members.players', DeveloperPlayerController::class);

            Route::get('groups/{group}/members/{member}/players/{player}/submit-prediction', [DeveloperPlayerController::class, 'createPrediction'])->name('groups.members.players.submit-prediction.create');
            Route::post('groups/{group}/members/{member}/players/{player}/submit-prediction', [DeveloperPlayerController::class, 'submitPrediction'])->name('groups.members.players.submit-prediction');
            Route::get('groups/{group}/members/{member}/players/{player}/predictions/{prediction}/edit', [DeveloperPlayerController::class, 'editPrediction'])->name('groups.members.players.predictions.edit');
            Route::patch('groups/{group}/members/{member}/players/{player}/predictions/{prediction}', [DeveloperPlayerController::class, 'updatePrediction'])->name('groups.members.players.predictions.update');
            Route::delete('groups/{group}/members/{member}/players/{player}/predictions/{prediction}', [DeveloperPlayerController::class, 'destroyPrediction'])->name('groups.members.players.predictions.destroy');
        });
    });
});
