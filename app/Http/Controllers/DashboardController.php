<?php

namespace App\Http\Controllers;

use App\Services\Contracts\UserQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * DashboardController handles the main user dashboard.
 *
 * This controller is responsible for displaying the user's personalized dashboard,
 * which serves as the central hub for managing groups and accessing prediction features.
 * It aggregates user-specific data to provide an overview of their activity in the app.
 */
class DashboardController extends Controller
{
    public function __construct(private UserQueryInterface $userQueryService) {}

    /**
     * Display the user dashboard.
     *
     * This method retrieves and displays the authenticated user's dashboard,
     * which shows their groups, quick stats, and provides navigation to key features.
     * The dashboard acts as the entry point after login, helping users understand
     * their current state in the application and guiding them toward next actions.
     *
     * @param  Request  $request  The incoming HTTP request containing user session data
     * @return View Returns the dashboard view with user-specific data
     */
    public function index(Request $request): View
    {
        // get the authenticated user
        $user = $request->user();

        // retrieve all groups the user can access: groups they own or are members of
        $groups = $this->userQueryService->getAccessibleGroups($user);

        return view('dashboard', [
            'groups' => $groups,
            'user' => $user,
        ]);
    }
}
