<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

/**
 * DashboardController handles the main user dashboard.
 *
 * This controller is responsible for displaying the user's personalized dashboard,
 * which serves as the central hub for managing groups and accessing prediction features.
 * It aggregates user-specific data to provide an overview of their activity in the app.
 */
class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     *
     * This method retrieves and displays the authenticated user's dashboard,
     * which shows their groups, quick stats, and provides navigation to key features.
     * The dashboard acts as the entry point after login, helping users understand
     * their current state in the application and guiding them toward next actions.
     *
     * @param Request $request The incoming HTTP request containing user session data
     * @return View Returns the dashboard view with user-specific data
     */
    public function index(Request $request): View
    {
        // get the authenticated user
        $user = $request->user();

        // retrieve all groups the user can access: groups they own or are members of
        $groups = $user->getAccessibleGroups();

        // Return the dashboard view with the data needed for display
        // Currently we pass groups and user data; this will expand as we add
        // more dashboard features like upcoming games, recent predictions, etc.
        return view('dashboard', [
            'groups' => $groups,
            'user' => $user,
        ]);
    }
}