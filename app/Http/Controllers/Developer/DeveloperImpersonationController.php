<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeveloperImpersonationController extends Controller
{
    /**
     * Start an impersonation session as the selected user.
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        $developer = $request->user();

        // Keep this guard in the controller as a safety net in case route middleware changes.
        if ($developer->role !== UserRole::DEVELOPER->value) {
            abort(403, 'Only developers can impersonate users.');
        }

        if ($request->session()->has('impersonator_user_id')) {
            $this->setFlashAlert('warning', 'You are already viewing the application as another user. Return to your own view first.');

            return redirect()->route('dashboard');
        }

        if ($developer->id === $user->id) {
            $this->setFlashAlert('info', 'You are already viewing the application as this user.');

            return redirect()->route('dashboard');
        }

        $request->session()->put('impersonator_user_id', $developer->id);
        $request->session()->put('impersonator_user_name', $developer->name);
        $request->session()->put('impersonated_user_id', $user->id);
        $request->session()->put('impersonated_user_name', $user->name);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        $this->setFlashAlert('warning', 'You are now viewing the application as '.$user->name.'.');

        $groupUlid = $request->query('group');

        if (is_string($groupUlid) && $groupUlid !== '') {
            $group = Group::query()->where('ulid', $groupUlid)->first();

            if ($group instanceof Group) {
                return redirect()->route('groups.show', ['group' => $group->ulid]);
            }
        }

        return redirect()->route('dashboard');
    }

    /**
     * Stop impersonation and restore the original developer account.
     */
    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_user_id');
        $request->session()->forget(['impersonator_user_name', 'impersonated_user_id', 'impersonated_user_name']);

        if (! is_numeric($impersonatorId)) {
            $this->setFlashAlert('warning', 'No impersonation session was active.');

            return redirect()->route('dashboard');
        }

        $impersonator = User::query()->find((int) $impersonatorId);

        if (! $impersonator instanceof User) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        if ($impersonator->role !== UserRole::DEVELOPER->value) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        Auth::guard('web')->login($impersonator);
        $request->session()->regenerate();

        $this->setFlashAlert('success', 'Returned to your developer account.');

        return redirect()->route('developer.users.index');
    }
}