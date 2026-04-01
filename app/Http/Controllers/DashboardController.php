<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $currentUser = auth()->user();
        $showAdminStats = $currentUser
            ? (
                $currentUser->hasPermission('manage_users')
                || $currentUser->hasPermission('manage_roles')
                || $currentUser->hasPermission('manage_applications')
                || $currentUser->hasPermission('manage_sessions')
                || $currentUser->hasPermission('view_audit_logs')
            )
            : false;

        $registeredApplications = Application::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $accessibleApplicationIds = $currentUser
            ? $currentUser->applications()->pluck('id')->all()
            : [];

        $availableApplications = $registeredApplications->map(function (Application $application) use ($accessibleApplicationIds) {
            $application->setAttribute('is_accessible', in_array($application->id, $accessibleApplicationIds, true));

            return $application;
        });

        $accessibleApplications = $availableApplications->filter(fn (Application $application) => (bool) $application->getAttribute('is_accessible'));

        return view('dashboard', [
            'showAdminStats' => $showAdminStats,
            'userCount' => $showAdminStats ? User::count() : null,
            'activeUserCount' => $showAdminStats ? User::where('is_active', true)->count() : null,
            'roleCount' => $showAdminStats ? Role::count() : null,
            'applicationCount' => $showAdminStats ? Application::count() : null,
            'availableApplications' => $availableApplications,
            'accessibleApplicationCount' => $accessibleApplications->count(),
        ]);
    }
}
