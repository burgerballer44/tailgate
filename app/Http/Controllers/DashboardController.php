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
    /**
     * Build the dashboard controller with the query service used to fetch user-scoped data.
     *
     * @param UserQueryInterface $userQueryService Service responsible for querying user dashboard data.
        * @return void Initializes controller dependencies.
     */
    public function __construct(private UserQueryInterface $userQueryService) {}

    /**
     * Render the authenticated user's dashboard and the groups they can access.
     *
     * @param  Request  $request  The active request used to resolve the signed-in user.
     * @return View The dashboard view populated with the user and accessible groups.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $groups = $this->userQueryService->getAccessibleGroups($user);

        return view('dashboard', [
            'groups' => $groups,
            'user' => $user,
        ]);
    }
}
